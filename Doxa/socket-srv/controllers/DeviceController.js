import pool from "../db/db.js";

class DeviceController {
    async ping(data) {
        
        let invdate = new Date(
            new Date().toLocaleString("en-US", {
                timeZone: "Asia/Tbilisi",
            })
        );

        function padZero(num) {
            return num.toString().padStart(2, "0");
        }

        let time_now = `${invdate.getFullYear()}-${padZero(
            invdate.getMonth() + 1
        )}-${padZero(invdate.getDate())} ${padZero(
            invdate.getHours()
        )}:${padZero(invdate.getMinutes())}:${padZero(invdate.getSeconds())}`;

        console.log("ping", time_now, data);

        let sql = `UPDATE point_devices SET updated_at = '${time_now}', point = ${data.point_id} WHERE id = ${data.device_id} AND active = 1`;
        let [fields, result] = await pool.query(sql);
    }
}

export default new DeviceController();
