# Project EAV API

Live project is deployed on [eav.ivan-larionov.dev](https://eav.ivan-larionov.dev)
Github repo is [here](https://github.com/ivanthecrazy/eav_api)
API documentation is [here](https://eav.ivan-larionov.dev/docs/index.html)

## A few comments

I tried to follow the task as closely as possible. However, I had to make a few assumptions:

- I assumed that users can access only projects that they are assigned to.
- I assumed that users can only access timesheets for the projects that they are assigned to.
- I assumed that users can create any attributes

There are many infinite ways to improve this futher, but I think we have a reasonable amount of things done. It's not clear where I had to stop.


## Setup

### Clone the repository

```bash
git clone git@github.com:ivanthecrazy/eav_api.git
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
php artisan passport:client --personal
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

Please refer to [this page](https://eav.ivan-larionov.dev/docs/index.html#) for the API documentation.


## Sample credentials

Use the following token or obtain a new one using the API above:

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZTJkZDgyMS1jYmVlLTQ5YmMtYWY4YS04ZmI3ZTVlNGE2MjgiLCJqdGkiOiJhODQ2YjUwNGZjZTQzZDVlYWQxNjNmZGFiMTU2OTdkNWU3MzVlNWZiMDc5ODc1NTM1NTE1Mjc3NzYyMTkyZjZhYTM4ZTBiZDIwODRiNjQyMCIsImlhdCI6MTczOTE5NzQyNS42MDE1MDQsIm5iZiI6MTczOTE5NzQyNS42MDE1MDgsImV4cCI6MTc3MDczMzQyNS41OTAzNDgsInN1YiI6IjE2Iiwic2NvcGVzIjpbXX0.eE1_W1aGY8FVe750-SZho9XAXr267NsCTH8_FqDIvkljO3Pxg2bXg-MHDkONff-yEpnN2eV38spxoNBaE_A0uba_ifgQ35_OlkZwpVqZM8Lf3Rkt8DPq0vWqDvJicgNJqJ0ktE6T8tfKIb5J2sO8DKULL999jBtHCEnnquQ6NBN4i86Nurxep18EPOjMpLBdwAfnZx838GIeHsKcXpazszqBLEEugYKMUecZCTeDIJqqCH1xvCikvb_ao1RcxThk18LbuMszxnuOtCr_VBOZNZhTLKnAzqQ7sEbTQIgpUe6HF1g1RFHY40lX_XZA5TirbJCLOeB7G_adkjWLTKKnbRMbq2kYwG6rWxP2I95J-gTtd-ttbUZGbQ_3tK7f8bA0li6t7wIBoDQlv9y9T_uwNer9xjCeMzpb4xzx1E1IFSyBDoD35rsNCXGbztupwxUMmP8o7s3scoUPf2r963yQy2vSLVglORUrCXf8d3_uweYyKcZKM94QzWQbQjBrlFRmuGb_Mx9RLK1HsXG_DJhz090fHxrIq2eAJRFt3ak-OcE2xp9HP-rDYoUT8cZLifCpvcnjn613EM2tQrvRIzzAfRtm2jt3mZt7qn_YEbCRG0xngPWMhJH0AYmVgjvJFf8ngtWyWGzoW5R0TFiCN34IGQellVyT_aQLrf5W_tOI_bk
```