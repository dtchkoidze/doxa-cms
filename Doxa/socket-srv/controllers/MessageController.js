import pool from "../db/db.js";

class MessageController {
    async handleMessage(msg) {
        console.log("Message handling in controller: ", msg);
    }
}

export default new MessageController();
