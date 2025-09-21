import { createInertiaApp } from "@inertiajs/vue3";
import require$$0 from "process";
import require$$1 from "http";
import { createSSRApp, h } from "vue";
import { renderToString } from "@vue/server-renderer";
function getDefaultExportFromCjs(x) {
  return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, "default") ? x["default"] : x;
}
var lib = {};
var hasRequiredLib;
function requireLib() {
  if (hasRequiredLib) return lib;
  hasRequiredLib = 1;
  Object.defineProperty(lib, "__esModule", {
    value: true
  });
  lib.default = void 0;
  var process = _interopRequireWildcard(require$$0);
  var _http = require$$1;
  function _getRequireWildcardCache(nodeInterop) {
    if (typeof WeakMap !== "function") return null;
    var cacheBabelInterop = /* @__PURE__ */ new WeakMap();
    var cacheNodeInterop = /* @__PURE__ */ new WeakMap();
    return (_getRequireWildcardCache = function(nodeInterop2) {
      return nodeInterop2 ? cacheNodeInterop : cacheBabelInterop;
    })(nodeInterop);
  }
  function _interopRequireWildcard(obj, nodeInterop) {
    if (obj && obj.__esModule) {
      return obj;
    }
    if (obj === null || typeof obj !== "object" && typeof obj !== "function") {
      return { default: obj };
    }
    var cache = _getRequireWildcardCache(nodeInterop);
    if (cache && cache.has(obj)) {
      return cache.get(obj);
    }
    var newObj = {};
    var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor;
    for (var key in obj) {
      if (key !== "default" && Object.prototype.hasOwnProperty.call(obj, key)) {
        var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null;
        if (desc && (desc.get || desc.set)) {
          Object.defineProperty(newObj, key, desc);
        } else {
          newObj[key] = obj[key];
        }
      }
    }
    newObj.default = obj;
    if (cache) {
      cache.set(obj, newObj);
    }
    return newObj;
  }
  const readableToString = (readable) => new Promise((resolve, reject) => {
    let data = "";
    readable.on("data", (chunk) => data += chunk);
    readable.on("end", () => resolve(data));
    readable.on("error", (err) => reject(err));
  });
  var _default = (render, port) => {
    const _port = port || 13714;
    const routes = {
      "/health": async () => ({
        status: "OK",
        timestamp: Date.now()
      }),
      "/shutdown": () => process.exit(),
      "/render": async (request) => render(JSON.parse(await readableToString(request))),
      "/404": async () => ({
        status: "NOT_FOUND",
        timestamp: Date.now()
      })
    };
    (0, _http.createServer)(async (request, response) => {
      const dispatchRoute = routes[request.url] || routes["/404"];
      try {
        response.writeHead(200, {
          "Content-Type": "application/json",
          "Server": "Inertia.js SSR"
        });
        response.write(JSON.stringify(await dispatchRoute(request)));
      } catch (e) {
        console.error(e);
      }
      response.end();
    }).listen(_port, () => console.log("Inertia SSR server started."));
    console.log(`Starting SSR server on port ${_port}...`);
  };
  lib.default = _default;
  return lib;
}
var libExports = requireLib();
const createServer = /* @__PURE__ */ getDefaultExportFromCjs(libExports);
async function resolvePageComponent(path, pages) {
  for (const p of Array.isArray(path) ? path : [path]) {
    const page = pages[p];
    if (typeof page === "undefined") {
      continue;
    }
    return typeof page === "function" ? page() : page;
  }
  throw new Error(`Page not found: ${path}`);
}
createServer(
  (page) => createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, /* @__PURE__ */ Object.assign({ "./Pages/Welcome.vue": () => import("./assets/Welcome-CQ3ctrql.js") })),
    setup({ App, props, plugin }) {
      return createSSRApp({ render: () => h(App, props) }).use(plugin);
    }
  })
);
