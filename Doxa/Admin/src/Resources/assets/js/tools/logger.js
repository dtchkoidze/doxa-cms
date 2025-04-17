let style = "color: yellow; background-color: black; font-size: 11px;";

export function color_log(message, ...args) {
    // provide key and value like this : clog('this.test', this.test);
    
    if (typeof message !== "string" || typeof style !== "string") {
        console.error("color_log expects a string message and a string style.");
        return;
    }

    let error = new Error();
    let stack = error.stack.split("\n");


    
    let caller_func = stack[1] ? stack[1].trim().split("@")[0] : "Unknown function";

    let file_and_line = stack[1] ? stack[1].trim().split("/") : "Unknown file";

    let caller_file = file_and_line[file_and_line.length - 1].split(":")[0];

    caller_file = caller_file.split("?")[0];

    
    let location = `${caller_func} @ ${caller_file}`;

    


    console.log(`%c[${location}] ${message}`, style, ...args);
}
