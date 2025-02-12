# Voting App

This is a command-line based voting application built using PHP and MySQL.

## Installation

```sh
# Clone the repository
git clone https://github.com/anujkattel/pbl.git

# Navigate to the project directory
cd pbl
```

## Configuration

```sh
# Set up database configuration in config.php
# Update config.php with your database credentials
```

## Running the Application

```sh
# Start the PHP built-in server
php -S localhost:8000
```

## Usage

```sh
# Cast a vote
curl -X POST -d "candidate=Candidate Name" http://localhost:8000/vote.php

# View results
curl http://localhost:8000/results.php
```

## Deployment

```sh
# Upload project files to a web server
scp -r * user@server:/path/to/project
```

## Git Workflow

```sh
# Create a new branch
git checkout -b feature-branch

# Stage changes
git add .

# Commit changes
git commit -m "Add new feature"

# Push changes
git push origin feature-branch

# Create a pull request on GitHub
```

## License

```sh
# This project is licensed under the MIT License.
