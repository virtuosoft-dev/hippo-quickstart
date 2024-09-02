const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const app = express();
const port = 3000;

// Define the upload directory
const UPLOAD_DIR = path.join(__dirname, 'uploads');

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

// Set up storage for multer
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        if (!fs.existsSync(UPLOAD_DIR)) {
            fs.mkdirSync(UPLOAD_DIR);
        }
        cb(null, UPLOAD_DIR);
    },
    filename: (req, file, cb) => {
        cb(null, `${Date.now()}-${file.originalname}`);
    }
});

// Set up file filter for multer
const fileFilter = (req, file, cb) => {
    if (ALLOWED_MIME_TYPES.includes(file.mimetype)) {
        cb(null, true);
    } else {
        cb(new Error('Invalid file type'), false);
    }
};

const upload = multer({ storage: storage, fileFilter: fileFilter });

// Endpoint to handle file upload
app.post('/', upload.single('file'), (req, res) => {
    const jobId = req.query.job_id;

    // Validate job_id by checking if the file exists
        const jobFilePath = `/tmp/devstia_${jobId}-user.json`;
        if (!fs.existsSync(jobFilePath)) {
            return res.status(400).json({ status: 'error', message: 'Invalid job_id.' });
        }
    
        if (!req.file) {
            return res.status(400).json({ status: 'error', message: 'No file uploaded.' });
        }
    
        // Get the file extension
        const fileExtension = path.extname(req.file.originalname);
        const newFilePath = `/tmp/devstia_${jobId}-import${fileExtension}`;
    
        // Move the file to the new location
        fs.rename(req.file.path, newFilePath, (err) => {
            if (err) {
                console.error('Failed to move the file:', err);
                return res.status(500).json({ status: 'error', message: 'File upload failed.' });
            }
            res.json({ status: 'uploaded', message: 'File uploaded. Please click continue.' });
        });
});

app.use((err, req, res, next) => {
    if (err) {
        res.status(400).send(err.message);
    } else {
        next();
    }
});

app.listen(port, () => {
    console.log(`Server is running on http://localhost:${port}`);
});