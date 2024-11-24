<?php
$content = "OPENAI_API_KEY=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
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=epm_database";

file_put_contents(__DIR__ . '/.env', $content);
echo "Created .env file in: " . __DIR__;
?> 