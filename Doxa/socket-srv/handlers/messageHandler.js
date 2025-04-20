import pool from "../db/db.js";

import MessageController from "../controllers/MessageController.js";
import DeviceController from "../controllers/DeviceController.js";

export default function messageHandler(io, socket) {
    
    socket.on("message", function (data) {
        console.log("Message received with data: ", data);
        // can handle things here or pass to controllers:
    });

    socket.on("test_message", function (data) {
        MessageController.handleMessage(data);
    });

    socket.on("ping_device", function (data) {
        DeviceController.ping(data);
    });
}
