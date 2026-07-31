SELECT 'CREATE DATABASE spa_base_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'spa_base_test')\gexec
