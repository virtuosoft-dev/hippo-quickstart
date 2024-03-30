/**
 * SQL PHP Serialized Replacement (SPSR) tool for safely replacing strings
 * in SQL 'quick' dumps that contain PHP serialized data.
 */

// Get the file, search string, replace string from the command line
const filePath = process.argv[2];
const searchString = process.argv[3];
const replaceString = process.argv[4];
if (!filePath || !searchString || !replaceString) {
    console.error('Please provide a file path, search string, and replace string.');
    process.exit(1);
}

// Load the file and replace the strings
const fs = require('fs');
const readline = require('readline');
const path = require('path');
processFile(filePath, searchString, replaceString);

function processFile(filePath, searchString, replaceString) {
    const fileStream = fs.createReadStream(filePath);
    const rl = readline.createInterface({
        input: fileStream,
        crlfDelay: Infinity
    });

    const tempFilePath = path.join(path.dirname(filePath), 'output.sql');
    const writeStream = fs.createWriteStream(tempFilePath);
    const regex = /('.*?'|[^',\s]+)(?=\s*,|\s*;|\s*$)/g;

    rl.on('line', (origLine) => {
        let line = origLine.trim();
        if (line.indexOf(searchString) !== -1 &&
            line.startsWith("(") &&
            (line.endsWith("),") || line.endsWith(");"))) {
            const startLine = line.substring(0, 1);
            const endLine = line.substring(line.length - 2);
            line = line.substring(1, line.length - 2);
            line = line.replace(/\\0/g, '~0Placeholder');
            line = startLine + line.match(regex).map(item => {
                item = item.trim();
                if (item.startsWith("'") && item.endsWith("'")) {
                    item = item.substring(1, item.length - 1);
                    item = item.replaceAll(searchString, replaceString);
                    if (isSerialized(item)) {

                        // Recalculate the length of the serialized strings
                        item = JSON.parse(JSON.stringify(item.replaceAll('\\', '')));
                        item = item.replaceAll('~0Placeholder', '\0');
                        let ret = item.replace(/s:(\d+):"(.*?)";/gms, function(match, p1, p2) {
                            return 's:' + p2.length + ':"' + p2 + '";';
                        });
                        item = addslashes(ret);
                    }
                    return "'" + item + "'";
                } else if (item === 'null') {
                    return null;
                } else if (isNaN(item)) {
                    return item;
                } else {
                    return Number(item);
                }
            }) + endLine;
        }
        writeStream.write(line + '\n');
    });
    rl.on('close', () => {
        writeStream.end();
        //fs.renameSync(tempFilePath, filePath);
    });
}

function addslashes(str) {
    return str.replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0').replace(/[\u0080-\uFFFF]/g, function(ch) {
        var code = ch.charCodeAt(0).toString(16);
        while (code.length < 4) {
            code = '0' + code;
        }
        return '\\u' + code;
    });
}

function isSerialized(data, strict = true) {

    // // If it isn't a string, it isn't serialized.
    // if (typeof data !== 'string') {
    //     return false;
    // }
    if (data[1] !== ':') {
        return false;
    }
    if (data.length < 4) {
        return false;
    }
    if (data === 'N;') {
        return true;
    }
    if (strict) {
        const lastc = data[data.length - 1];
        if (lastc !== ';' && lastc !== '}') {
            return false;
        }
    } else {
        const semicolon = data.indexOf(';');
        const brace = data.indexOf('}');
        // Either ; or } must exist.
        if (semicolon === -1 && brace === -1) {
            return false;
        }
        // But neither must be in the first X characters.
        if (semicolon !== -1 && semicolon < 3) {
            return false;
        }
        if (brace !== -1 && brace < 4) {
            return false;
        }
    }
    const token = data[0];
    switch (token) {
        case 's':
            if (strict) {
                if (data[data.length - 2] !== '"') {
                    return false;
                }
            } else if (!data.includes('"')) {
                return false;
            }
            // Or else fall through.
        case 'a':
        case 'O':
        case 'E':
            return Boolean(data.match(new RegExp("^" + token + ":[0-9]+:")));
        case 'b':
        case 'i':
        case 'd':
            const end = strict ? '$' : '';
            return Boolean(data.match(new RegExp("^" + token + ":[0-9.E+-]+;" + end)));
    }
    return false;
}
