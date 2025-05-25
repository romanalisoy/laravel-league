# Champions League

This project is a mini league simulation—built with Laravel, Docker, and AJAX—that mimics a Champions League‐style competition. 
You’ll see teams, weekly fixtures, an up-to-date league table, match results, and (from Week 4 onward) championship predictions powered by Monte Carlo simulation.

## Features

- **Dynamic fixture generation**  
  Double round‐robin schedule generated automatically based on the number of teams.

- **AJAX controls (no full page reload)**  
  `Next Week`, `Play All`, and `Edit Match` operations all happen via AJAX.

- **Championship predictions**  
  After Week 4, run Monte Carlo simulations on the remaining matches to estimate each team’s chance of winning.

- **Containerized environment**  
  Full isolation via Docker & Docker Compose.

- **Automated tests**  
  Unit tests for `LeagueService` and feature (integration) tests for the API.

- **CI-ready**  
  Includes a sample GitHub Actions workflow to run tests and code style checks on every pull request.

## Getting Started

```bash # Clone the repository and set up the environment
  git clone https://github.com/romanalisoy/laravel-league.git
  cd laravel-league
```
### Build and start the Docker containers
```bash # 
  docker-compose up -d --build
```

### Launch the application
```bash # Open your browser and go to
  http://localhost:8000
```

### Run the tests
```bash # Run unit and feature tests
  docker-compose exec app php artisan test
```
