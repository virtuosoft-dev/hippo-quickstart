const fs = require('fs');
const util = require('util');
const path = require('path');
const express = require('express');
const fileUpload = require('express-fileupload');
const { log } = require('console');

const app = express();
const port = 4999;
const activityFilePath = '/tmp/quickstart_upload_activity';
const logFilePath = '/tmp/upload-server.log';
const idleTimeout = 15 * 60 * 1000; // 15 minutes in milliseconds

// Promisify fs functions
const stat = util.promisify(fs.stat);

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

// Endpoint to handle file upload
app.post('/quickstart-upload/', async (req, res) => {
    const jobId = req.query.job_id;
    logMessage(`Received file upload request for job_id: ${jobId}`);

    // Check for undefined job_id
    if (!jobId) {
        logMessage('job_id is required.');
        return res.status(400).json({ status: 'error', message: 'job_id is required.' });
    }

    // Validate jobId
    if (!jobId === 'string' || jobId.trim().length === 0) {
        logMessage('Invalid job_id format.');
        return res.status(400).json({ status: 'error', message: 'Invalid job_id format.' });
    }

    // Validate job_id by checking if the file exists
    const jobFilePath = path.join('/tmp', `devstia_${jobId}-user.json`);
    try {
        if (!fs.existsSync(jobFilePath)) {
            logMessage('Invalid job_id: File does not exist.');
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File does not exist.' });
        }
        const isOwnedByAdmin = await checkFileOwnership(jobFilePath, 'admin');
        if (!isOwnedByAdmin) {
            logMessage('Invalid job_id: File is not owned by admin.');
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File is not owned by admin.' });
        }
    } catch (err) {
        logMessage('Error checking file ownership.');
        return res.status(500).json({ status: 'error', message: 'Error checking file ownership.' });
    }

    if (!req.files || Object.keys(req.files).length === 0) {
        logMessage('No file uploaded.');
        return res.status(400).json({ status: 'error', message: 'No file uploaded.' });
    }

    const uploadedFile = req.files.file;

    // Check file type
    if (!ALLOWED_MIME_TYPES.includes(uploadedFile.mimetype)) {
        logMessage('Invalid file type.');
        return res.status(400).json({ status: 'error', message: 'Invalid file type.' });
    }

    // Touch the activity file
    touchFile(activityFilePath);

    // Get the file extension
    const fileExtension = path.extname(uploadedFile.name);
    const newFilePath = path.join('/tmp', `devstia_${jobId}-import${fileExtension}`);

    // Move the file to the new location
    uploadedFile.mv(newFilePath, (err) => {
        if (err) {
            logMessage('Failed to move the file.');
            console.error('Failed to move the file:', err);
            return res.status(500).json({ status: 'error', message: 'File upload failed.' });
        }
        logMessage('File uploaded successfully.');
        res.json({ status: 'uploaded', message: 'File uploaded. Please click continue.' });
    });
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