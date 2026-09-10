group "default" {
  targets = ["php"]
}

target "php" {
  context = "."
  dockerfile = "Dockerfile"
  target = "frankenphp_prod"
  tags = ["app-php-prod"]
}
