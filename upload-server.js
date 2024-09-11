const fs = require('fs');
const path = require('path');
const util = require('util');
const os = require('os');
const express = require('express');
const fileUpload = require('express-fileupload');

const app = express();
const port = 4999;
const activityFilePath = '/home/admin/tmp/quickstart_upload_activity';
const logFilePath = '/tmp/upload-server.log';
const idleTimeout = 15 * 60 * 1000; // 15 minutes in milliseconds

// Promisify fs functions
const stat = util.promisify(fs.stat);

// Define allowed MIME types
const ALLOWED_MIME_TYPES = [
    'application/zip',
    'application/x-xz',
    'application/octet-stream',
    'application/gzip',
    'application/x-rar-compressed',
    'application/x-tar',
    'application/x-bzip2',
    'application/x-7z-compressed'
];

// Middleware to handle file uploads
app.use(fileUpload());

// Inline logging function
const logMessage = (message) => {
    const timestamp = new Date().toISOString();
    const logEntry = `[${timestamp}] ${message}\n`;
    fs.appendFileSync(logFilePath, logEntry, 'utf8');
};

// Function to touch a file
const touchFile = (filePath) => {
    const time = new Date();
    try {
        fs.utimesSync(filePath, time, time);
    } catch (err) {
        fs.closeSync(fs.openSync(filePath, 'w'));
    }
};

// Our setXferJobData function
const setXferJobData = (jobId, key, data) => {
    // Here we need to write the data to the /home/admin/tmp/devstia_${jobId}-${key}.json file 
    // with the content of data
    const filePath = path.join('/home/admin/tmp', `devstia_${jobId}-${key}.json`);
    fs.writeFileSync(filePath, JSON.stringify(data), 'utf8');

    // Log the operation
    logMessage(`Data written to file: ${filePath}`);
};

// Function to check file ownership
const checkFileOwnership = async (filePath, owner) => {
    try {
        const stats = await stat(filePath);
        const fileOwner = stats.uid; // Get the file owner's user ID
        const ownerUid = os.userInfo({ username: owner }).uid; // Get the user ID of the specified owner
        return fileOwner === ownerUid;
    } catch (err) {
        logMessage(`Error checking file ownership: ${err.message}`);
        throw err;
    }
};

logMessage('Server started.');

// Global error handler for uncaught exceptions
process.on('uncaughtException', (err) => {
    logMessage(`Uncaught Exception: ${err.message}`);
    console.error('Uncaught Exception:', err);
    process.exit(1); // Exit the process to avoid undefined states
});

// Global error handler for unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    logMessage(`Unhandled Rejection: ${reason}`);
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
    process.exit(1); // Exit the process to avoid undefined states
});

// Endpoint to handle file upload
app.post('/quickstart-upload/', async (req, res) => {
    try {
        const jobId = req.query.job_id;
        logMessage(`Received file upload request for job_id: ${jobId}`);

        // Check for undefined job_id
        if (!jobId) {
            logMessage('job_id is required.');
            return res.status(400).json({ status: 'error', message: 'job_id is required.' });
        }

        // Validate jobId
        if (typeof jobId !== 'string' || jobId.trim().length === 0) {
            logMessage('Invalid job_id format.');
            return res.status(400).json({ status: 'error', message: 'Invalid job_id format.' });
        }

        // Validate job_id by checking if the file exists
        const jobFilePath = path.join('/tmp', `devstia_${jobId}-user.json`);
        logMessage(`Checking if job file exists: ${jobFilePath}`);
        if (!fs.existsSync(jobFilePath)) {
            logMessage('Invalid job_id: File does not exist.');
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File does not exist.' });
        }

        const isOwnedByAdmin = await checkFileOwnership(jobFilePath, 'admin');
        logMessage(`File ownership check result: ${isOwnedByAdmin}`);
        if (!isOwnedByAdmin) {
            logMessage('Invalid job_id: File is not owned by admin.');
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File is not owned by admin.' });
        }

        if (!req.files || Object.keys(req.files).length === 0) {
            logMessage('No file uploaded.');
            return res.status(400).json({ status: 'error', message: 'No file uploaded.' });
        }

        const uploadedFile = req.files.file;
        logMessage(`Received file: ${uploadedFile.name}`);

        // Check file type
        if (!ALLOWED_MIME_TYPES.includes(uploadedFile.mimetype)) {
            logMessage('Invalid file type.');
            return res.status(400).json({ status: 'error', message: 'Invalid file type.' });
        }

        // Touch the activity file
        touchFile(activityFilePath);
        logMessage('Touched activity file.');

        // Get the file extension
        const fileExtension = path.extname(uploadedFile.name);
        const newFilePath = path.join('/tmp', `devstia_${jobId}-import${fileExtension}`);
        logMessage(`Moving file to: ${newFilePath}`);

        // Move the file to the new location
        uploadedFile.mv(newFilePath, (err) => {
            if (err) {
                logMessage('Failed to move the file.');
                console.error('Failed to move the file:', err);
                return res.status(500).json({ status: 'error', message: 'File upload failed.' });
            }
            logMessage('File uploaded successfully.');
            setXferJobData(jobId, 'import_file', newFilePath);
            return res.status(200).json({ status: 'uploaded', message: 'File uploaded. Please click continue.' });
        });
    } catch (err) {
        logMessage(`Error occurred: ${err.message}`);
        console.error('Error occurred:', err);
        return res.status(500).json({ status: 'error', message: 'Internal server error.' });
    }
});

// Periodically check for idle time
setInterval(() => {
    fs.stat(activityFilePath, (err, stats) => {
        if (err) {
            logMessage('Error checking activity file.');
            console.error('Error checking activity file:', err);
            return;
        }

        const lastModified = new Date(stats.mtime).getTime();
        const now = Date.now();

        if (now - lastModified > idleTimeout) {
            console.log('Server has been idle for more than 15 minutes. Shutting down...');
            logMessage('Server has been idle for more than 15 minutes. Shutting down...');
            process.exit(0);
        }
    });
}, 60 * 1000); // Check every minute

// Error-handling middleware
app.use((err, req, res, next) => {
    if (err) {
        logMessage(err.message);
        res.status(400).json({ status: 'error', message: err.message });
    } else {
        next();
    }
});

app.listen(port, () => {
    logMessage(`Server is running on http://localhost:${port}`);
    console.log(`Server is running on http://localhost:${port}`);
});