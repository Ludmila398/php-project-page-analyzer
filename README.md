### Hexlet tests and linter status
[![Actions Status](https://github.com/Ludmila398/php-project-9/workflows/hexlet-check/badge.svg)](https://github.com/Ludmila398/php-project-9/actions)
[![Linter check](https://github.com/Ludmila398/php-project-9/actions/workflows/linter-check.yml/badge.svg)](https://github.com/Ludmila398/php-project-9/actions/workflows/linter-check.yml)
[![Maintainability](https://api.codeclimate.com/v1/badges/246a453572d1635256b7/maintainability)](https://codeclimate.com/github/Ludmila398/php-project-9/maintainability)

### Project description

[Page Analyzer](https://php-project-9-production-a6ce.up.railway.app/) Приложение позволяет проверить веб-сайты на SEO пригодность по аналогии с PageSpeed Insights (на наличие тегов H1, TITLE и DESCRIPTION на главной странице сайта).
The application allows you to check websites for SEO suitability similar to PageSpeed Insights (existing H1, TITLE and DESCRIPTION tags on the main page of the site).

### Requirements

- PHP >= 8.1
- Composer

### Installation

Clone the repo and enter the project folder
```
git clone git@github.com:Ludmila398/php-project-9.git

cd php-project-9
```
Install the app
```
make install
```
Create a new database and import the database.sql file into the newly created database

Run the web server
```
make start
```
Open your browser and navigate to http://localhost:8000 to view the pages

--