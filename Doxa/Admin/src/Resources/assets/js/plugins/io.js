import { io } from "socket.io-client";
let app_mode = import.meta.env.VITE_APP_ENV;
let app_url = import.meta.env.VITE_APP_URL;
let app_host = import.meta.env.VITE_APP_HOST;
let url = "";
let port = import.meta.env.VITE_WEBSOCKET_PORT;
if (app_mode === "local") {
    url = `http://localhost:${port}`;
} else {
    url = `wss://${app_host}`;
}

const socket = io(url, {
    transports: ["websocket"],
    path: "/socket.io",
    autoConnect: true,
    reconnection: true,
    reconnectionAttempts: 5,
    reconnectionDelay: 1000,
});

socket.on("connect", () => {
    console.log("Admin io Connected to socket io");
});

socket.on("disconnect", () => {
    console.log("Disonnected to socket io");
});

/**
 * these are for testing purposes:
 */

socket.emit("test_message", { data: "this is data" });

export default socket;
