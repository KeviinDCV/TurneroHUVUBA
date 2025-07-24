<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Turno;
use App\Models\TurnoHistorial;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;

class TurnoHistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->servicio = Servicio::create([
            'nombre' => 'CITAS TEST',
            'codigo' => 'CT',
            'estado' => 'activo',
            'nivel' => 'servicio',
            'orden' => 1
        ]);
    }

    /**
     * Test que verifica que se crea un backup automáticamente cuando se crea un turno
     */
    public function test_turno_creation_creates_backup(): void
    {
        // Verificar que no hay registros inicialmente
        $this->assertEquals(0, Turno::count());
        $this->assertEquals(0, TurnoHistorial::count());

        // Crear un turno
        $turno = Turno::crear($this->servicio->id);

        // Verificar que se creó el turno
        $this->assertEquals(1, Turno::count());

        // Verificar que se creó el backup automáticamente
        $this->assertEquals(1, TurnoHistorial::count());

        $backup = TurnoHistorial::first();
        $this->assertEquals($turno->id, $backup->turno_original_id);
        $this->assertEquals('creacion', $backup->tipo_backup);
        $this->assertEquals($turno->codigo, $backup->codigo);
        $this->assertEquals($turno->numero, $backup->numero);
        $this->assertEquals($turno->servicio_id, $backup->servicio_id);
        $this->assertEquals($turno->estado, $backup->estado);
    }

    /**
     * Test que verifica que se crea un backup automáticamente cuando se actualiza un turno
     */
    public function test_turno_update_creates_backup(): void
    {
        // Crear un turno
        $turno = Turno::crear($this->servicio->id);

        // Verificar backup inicial
        $this->assertEquals(1, TurnoHistorial::count());

        // Actualizar el turno
        $turno->update([
            'estado' => 'llamado',
            'fecha_llamado' => now()
        ]);

        // Verificar que se creó un nuevo backup
        $this->assertEquals(2, TurnoHistorial::count());

        $backupUpdate = TurnoHistorial::where('tipo_backup', 'actualizacion')->first();
        $this->assertNotNull($backupUpdate);
        $this->assertEquals($turno->id, $backupUpdate->turno_original_id);
        $this->assertEquals('llamado', $backupUpdate->estado);
        $this->assertNotNull($backupUpdate->datos_adicionales);
    }

    /**
     * Test que verifica que se crea un backup automáticamente cuando se elimina un turno
     */
    public function test_turno_deletion_creates_backup(): void
    {
        // Crear un turno
        $turno = Turno::crear($this->servicio->id);
        $turnoId = $turno->id;

        // Verificar backup inicial
        $this->assertEquals(1, TurnoHistorial::count());

        // Eliminar el turno
        $turno->delete();

        // Verificar que el turno fue eliminado
        $this->assertEquals(0, Turno::count());
        $this->assertNull(Turno::find($turnoId));

        // Verificar que se creó un backup de eliminación
        $this->assertEquals(2, TurnoHistorial::count());

        $backupDelete = TurnoHistorial::where('tipo_backup', 'eliminacion')->first();
        $this->assertNotNull($backupDelete);
        $this->assertEquals($turnoId, $backupDelete->turno_original_id);
        $this->assertNotNull($backupDelete->datos_adicionales);
    }

    /**
     * Test que verifica que la funcionalidad de emergencia no afecta el historial
     */
    public function test_emergency_cleanup_preserves_history(): void
    {
        // Crear varios turnos
        $turno1 = Turno::crear($this->servicio->id);
        $turno2 = Turno::crear($this->servicio->id);
        $turno3 = Turno::crear($this->servicio->id);

        // Actualizar algunos turnos para generar más backups
        $turno1->update(['estado' => 'llamado']);
        $turno2->update(['estado' => 'atendido']);

        // Verificar estado inicial
        $this->assertEquals(3, Turno::count());
        $this->assertEquals(5, TurnoHistorial::count()); // 3 creaciones + 2 actualizaciones

        // Simular limpieza de emergencia (mismo código que AdminController)
        $deletedCount = Turno::whereDate('fecha_creacion', Carbon::today())->delete();

        // Verificar que los turnos fueron eliminados
        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(0, Turno::count());

        // Verificar que el historial se mantiene intacto
        // NOTA: La eliminación por query builder no dispara eventos de modelo,
        // por lo que no se crean backups de eliminación en emergencias (esto es intencional)
        $this->assertEquals(5, TurnoHistorial::count()); // 3 creaciones + 2 actualizaciones

        // Verificar que todos los tipos de backup están presentes
        $this->assertEquals(3, TurnoHistorial::where('tipo_backup', 'creacion')->count());
        $this->assertEquals(2, TurnoHistorial::where('tipo_backup', 'actualizacion')->count());
        $this->assertEquals(0, TurnoHistorial::where('tipo_backup', 'eliminacion')->count()); // No se crean en emergencias
    }

    /**
     * Test que verifica las relaciones del modelo TurnoHistorial
     */
    public function test_turno_historial_relationships(): void
    {
        $turno = Turno::crear($this->servicio->id);
        $backup = TurnoHistorial::first();

        // Test relación con servicio
        $this->assertInstanceOf(Servicio::class, $backup->servicio);
        $this->assertEquals($this->servicio->id, $backup->servicio->id);

        // Test relación con turno original
        $this->assertInstanceOf(Turno::class, $backup->turnoOriginal);
        $this->assertEquals($turno->id, $backup->turnoOriginal->id);

        // Test relación inversa desde Turno
        $this->assertTrue($turno->historial()->exists());
        $this->assertEquals(1, $turno->historial()->count());
    }

    /**
     * Test que verifica los métodos auxiliares del modelo TurnoHistorial
     */
    public function test_turno_historial_helper_methods(): void
    {
        $turno = Turno::crear($this->servicio->id);
        $backup = TurnoHistorial::first();

        // Test código completo
        $expectedCodigo = $backup->codigo . '-' . str_pad($backup->numero, 3, '0', STR_PAD_LEFT);
        $this->assertEquals($expectedCodigo, $backup->codigo_completo);

        // Test scopes
        $this->assertEquals(1, TurnoHistorial::delDia()->count());
        $this->assertEquals(1, TurnoHistorial::porServicio($this->servicio->id)->count());
        $this->assertEquals(1, TurnoHistorial::porTipoBackup('creacion')->count());
        $this->assertEquals(0, TurnoHistorial::porTipoBackup('actualizacion')->count());
    }

    /**
     * Test que verifica el método de estadísticas
     */
    public function test_turno_historial_statistics(): void
    {
        // Crear varios turnos con diferentes estados
        $turno1 = Turno::crear($this->servicio->id);
        $turno2 = Turno::crear($this->servicio->id);

        $turno1->update(['estado' => 'llamado']);
        $turno1->update(['estado' => 'atendido']);

        $estadisticas = TurnoHistorial::obtenerEstadisticas();

        $this->assertArrayHasKey('total_turnos', $estadisticas);
        $this->assertArrayHasKey('por_estado', $estadisticas);
        $this->assertArrayHasKey('por_servicio', $estadisticas);
        $this->assertArrayHasKey('por_tipo_backup', $estadisticas);

        $this->assertEquals(4, $estadisticas['total_turnos']); // 2 creaciones + 2 actualizaciones
        $this->assertEquals(2, $estadisticas['por_tipo_backup']['creacion'] ?? 0);
        $this->assertEquals(2, $estadisticas['por_tipo_backup']['actualizacion'] ?? 0);
    }
}
