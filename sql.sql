CREATE TABLE users (
    id INT PRIMARY KEY NOT NULL,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    is_verified BOOLEAN NOT NULL,
    created_at TIMESTAMP NOT NULL,
    role VARCHAR(50) NOT NULL,
    year_of_joining INT NOT NULL,
    branch VARCHAR(100) NOT NULL,
    semester INT NOT NULL
);

