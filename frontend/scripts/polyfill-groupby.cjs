/* Polyfill for Object.groupBy for Node < 20
 * This file is preloaded via NODE_OPTIONS to ensure ESLint and its helpers
 * can use Object.groupBy on runners that don't yet provide it.
 */
if (typeof Object.groupBy !== 'function') {
    Object.defineProperty(Object, 'groupBy', {
        value: function (iterable, callback) {
            if (iterable == null) {
                throw new TypeError('Cannot convert undefined or null to object');
            }
            const map = new Map();
            for (const item of iterable) {
                const key = typeof callback === 'function' ? callback(item) : item[callback];
                const prop = String(key);
                if (!map.has(prop)) map.set(prop, []);
                map.get(prop).push(item);
            }
            const out = {};
            for (const [k, v] of map) out[k] = v;
            return out;
        },
        configurable: true,
        writable: true,
    });
}
