<?php
$models = [
    'tiny_face_detector_model-weights_manifest.json',
    'tiny_face_detector_model-shard1',
    'face_landmark_68_model-weights_manifest.json',
    'face_landmark_68_model-shard1',
    'face_recognition_model-weights_manifest.json',
    'face_recognition_model-shard1',
    'face_recognition_model-shard2',
];

$baseUrl = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';
$targetDir = __DIR__ . '/public/models/';
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

foreach ($models as $model) {
    echo "Downloading $model...\n";
    file_put_contents($targetDir . $model, file_get_contents($baseUrl . $model));
}
echo "Done.\n";
