import * as faceapi from "@vladmandic/face-api";

window.faceapi = faceapi;

setTimeout(() => {
    window.dispatchEvent(new Event("face-api-ready"));
    console.log("[Filament FaceAPI] face-api-ready dispatched", window.faceapi);
}, 0);
