var components = {
    "packages": [
        {
            "name": "tagmanager",
            "main": "tagmanager-built.js"
        }
    ],
    "baseUrl": "/assets"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}