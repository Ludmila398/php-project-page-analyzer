### Hexlet tests and linter status
[![Actions Status](https://github.com/Ludmila398/php-project-9/workflows/hexlet-check/badge.svg)](https://github.com/Ludmila398/php-project-9/actions)
[![Linter check](https://github.com/Ludmila398/php-project-page-analyzer/actions/workflows/linter-check.yml/badge.svg)](https://github.com/Ludmila398/php-project-page-analyzer/actions/workflows/linter-check.yml)
[![Maintainability](https://api.codeclimate.com/v1/badges/f05cfe5bffc0b6c567c4/maintainability)](https://codeclimate.com/github/Ludmila398/php-project-page-analyzer/maintainability)

## Project description

The Page Analyzer application is a tool for analyzing websites' SEO suitability, similar to Google's PageSpeed Insights. It checks for the presence of essential meta tags, such as H1, TITLE, and DESCRIPTION, on the main page of a given website. This tool helps developers and SEO specialists quickly evaluate the structural readiness of a website for search engine optimization.

## Requirements

- PHP >= 8.1
- Composer

## Installation

Clone the repo and enter the project folder:
```
git clone git@github.com:Ludmila398/php-project-page-analyzer.git

cd php-project-page-analyzer
```
Install dependencies using Composer.
Ensure that `make` is installed and available on your system. The `make install` command will use Composer to install dependencies and prepare the project:
```
make install
```
Create a new PostgreSQL database and import the database.sql file into the newly created database.

Run the web server
```
make start
```
Open your browser and navigate to http://localhost:8000 to view the pages
