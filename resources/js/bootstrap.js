import _ from 'lodash';
window._ = _;

/**
 * Importamos axios HTTP library
 */
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Configuración alternativa que no requiere servidor WebSocket.
 * En lugar de usar WebSockets, usaremos polling.
 */

// Definir un objeto Echo simulado para evitar errores cuando el código intenta usar Echo
window.Echo = {
    channel: function(channelName) {
        console.log('Channel solicitado (simulado):', channelName);
        return {
            listen: function(eventName, callback) {
                console.log('Listening (simulado) a:', eventName);
                // No hacemos nada, usaremos polling
                return this;
            },
            listenForWhisper: function(eventName, callback) {
                return this;
            }
        };
    },
    private: function(channelName) {
        return this.channel('private-' + channelName);
    },
    presence: function(channelName) {
        return this.channel('presence-' + channelName);
    },
    connector: {
        pusher: {
            connection: {
                bind: function(event, callback) {
                    // Simular conexión exitosa para evitar errores en consola
                    if (event === 'connected') {
                        setTimeout(function() {
                            callback();
                        }, 500);
                    }
                }
            }
        }
    }
};

console.log('✅ Usando modo de polling para actualizaciones de turnos en tiempo real');
