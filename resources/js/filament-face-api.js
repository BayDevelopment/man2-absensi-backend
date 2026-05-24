import * as faceapi from "@vladmandic/face-api";

window.faceapi = faceapi;
window.dispatchEvent(new Event("face-api-ready"));
