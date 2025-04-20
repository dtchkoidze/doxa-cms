import express from "express";
import { createServer } from "http";
import { Server } from "socket.io";
import cors from "cors";
import env from "./config/env.js";
import pool from "./db/db.js";
import messageHandler from "./handlers/messageHandler.js";
const app = express();

const server = createServer(app);

let cors_options = {};
console.log(env.APP_ENV);
if (env.APP_ENV == "local") {
    cors_options = {
        origin: "*",
    };
} else {
    cors_options = {
        origin: env.APP_URL,
    };
}

const io = new Server(server, {
    cors: cors_options,
});


app.use(cors(cors_options));


io.on("connection", (socket) => {
    console.log(`User connected: ${socket.id}`);
    messageHandler(io, socket);
});

const PORT = env.WEBSOCKET_PORT || 3000;
console.log(PORT);
server.listen(PORT, (req, res) => {
    console.log("request: ", req);
    console.log(`Server is running on port ${PORT}`);
});

