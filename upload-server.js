const fs = require('fs');
const path = require('path');
const express = require('express');
const fileUpload = require('express-fileupload');

const app = express();
const port = 4999;
const activityFilePath = '/tmp/quickstart_upload_activity';
const idleTimeout = 15 * 60 * 1000; // 15 minutes in milliseconds

// Middleware to handle file uploads
app.use(fileUpload());

// Function to touch a file
const touchFile = (filePath) => {
    const time = new Date();
    try {
        fs.utimesSync(filePath, time, time);
    } catch (err) {
        fs.closeSync(fs.openSync(filePath, 'w'));
    }
};

// Endpoint to handle file upload
app.post('/', async (req, res) => {
    const jobId = req.query.job_id;

    // Validate jobId
    if (!isValidJobId(jobId)) {
        return res.status(400).json({ status: 'error', message: 'Invalid job_id format.' });
    }

    // Validate job_id by checking if the file exists
    const jobFilePath = path.join('/tmp', `devstia_${jobId}-user.json`);
    try {
        if (!fs.existsSync(jobFilePath)) {
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File does not exist.' });
        }
        const isOwnedByAdmin = await checkFileOwnership(jobFilePath, 'admin');
        if (!isOwnedByAdmin) {
            return res.status(400).json({ status: 'error', message: 'Invalid job_id: File is not owned by admin.' });
        }
    } catch (err) {
        return res.status(500).json({ status: 'error', message: 'Error checking file ownership.' });
    }

    if (!req.files || Object.keys(req.files).length === 0) {
        return res.status(400).json({ status: 'error', message: 'No file uploaded.' });
    }

    const uploadedFile = req.files.file;

    // Check file type
    if (!ALLOWED_MIME_TYPES.includes(uploadedFile.mimetype)) {
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
            console.error('Failed to move the file:', err);
            return res.status(500).json({ status: 'error', message: 'File upload failed.' });
        }
        res.json({ status: 'uploaded', message: 'File uploaded. Please click continue.' });
    });
});

// Periodically check for idle time
setInterval(() => {
    fs.stat(activityFilePath, (err, stats) => {
        if (err) {
            console.error('Error checking activity file:', err);
            return;
        }

        const lastModified = new Date(stats.mtime).getTime();
        const now = Date.now();

        if (now - lastModified > idleTimeout) {
            console.log('Server has been idle for more than 15 minutes. Shutting down...');
            process.exit(0);
        }
    });
}, 60 * 1000); // Check every minute

app.use((err, req, res, next) => {
    if (err) {
        res.status(400).json({ status: 'error', message: err.message });
    } else {
        next();
    }
});

app.listen(port, () => {
    console.log(`Server is running on http://localhost:${port}`);
});