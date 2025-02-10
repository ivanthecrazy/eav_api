# Project EAV API

## A few comments

I tried to follow the task as closely as possible. However, I had to make a few assumptions:

- I assumed that users can access only projects that they are assigned to.
- I assumed that users can only access timesheets for the projects that they are assigned to.
- I assumed that users can create any attributes

There are many infinite ways to improve this futher, but I think we have a reasonable amount of things done. It's not clear where I had to stop.


## Setup

### Clone the repository

```bash
git clone https://github.com/yourusername/project-eav-api.git
```

### Install dependencies

```bash
composer install
```

### Create environment file

```bash
cp .env.example .env
```

Once copied, adjust the environment variables to your needs. This is mostly about the database credentials.

### Generate a key

```bash
php artisan key:generate
```


### Setup Passport

```bash
php artisan passport:client --password
```

Make sure to save the following credentials into your `.env` file:

```
PASSPORT_PASSWORD_CLIENT_ID=9e2bf661-ccac-4223-b627-aa8892e82c07
PASSPORT_PASSWORD_SECRET=vzmxnrB310LEl9HGUTSBbdioC6IjVZQAsjMCKYXJ
```

### Run the migrations (and seed the database)

```bash
php artisan migrate --seed
```

### Import the SQL file (if you want to do it instead of running the migrations)

```bash
mysql -u your_username -p your_database_name < eav_api.sql
```

### Run the tests

```bash
php artisan test
```

### Start the server

```bash
php artisan serve
```

## API Documentation
