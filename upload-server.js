const express = require('express');
const fileUpload = require('express-fileupload');
const path = require('path');
const fs = require('fs');
const os = require('os');

const app = express();
const port = 3000;

// Allowed MIME types
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

// Function to check file ownership
const checkFileOwnership = (filePath, owner) => {
    return new Promise((resolve, reject) => {
        fs.stat(filePath, (err, stats) => {
            if (err) {
                return reject(err);
            }
            // Get the username of the file owner
            const uid = stats.uid;
            const userInfo = os.userInfo({ uid });
            if (userInfo.username === owner) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    });
};

// Function to validate jobId
const isValidJobId = (jobId) => {
    // Allow only alphanumeric characters
    const regex = /^[a-zA-Z0-9_-]+$/;
    return regex.test(jobId);
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