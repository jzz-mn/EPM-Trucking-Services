<?php
$content = "OPENAI_API_KEY=your-api-key-goes-here
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=epm_database";

file_put_contents(__DIR__ . '/.env', $content);
echo "Created .env file in: " . __DIR__;
?> 