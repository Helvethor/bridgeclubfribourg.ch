#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Display information about the current project
	# Or about an error in project initialization
	php bin/console -V

	if [ -n "${DATABASE_URL:-}" ]; then
		echo 'Waiting for database server and creating database if needed...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ "$ATTEMPTS_LEFT_TO_REACH_DATABASE" -eq 0 ]; do
			DATABASE_ERROR=$(php bin/console doctrine:database:create --if-not-exists --no-interaction 2>&1)
			EXIT_CODE=$?
			printf '%s\n' "$DATABASE_ERROR"

			if [ "$EXIT_CODE" -eq 0 ]; then
				break
			fi

			if [ "$EXIT_CODE" -eq 255 ]; then
				# If the Doctrine command exits with 255, an unrecoverable error occurred
				ATTEMPTS_LEFT_TO_REACH_DATABASE=0
				break
			fi

			sleep 1
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Still waiting for database server... $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
			echo 'The database server is not up or not reachable:'
			echo "$DATABASE_ERROR"
			exit 1
		else
			echo 'Database is ready'
		fi

		if php bin/console doctrine:migrations:up-to-date --no-interaction >/tmp/migrations_status.txt 2>&1; then
			echo 'Existing schema detected; applying pending migrations...'
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
		else
			echo 'No existing schema detected; creating schema from current metadata...'
			php bin/console doctrine:schema:create --no-interaction
		fi
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
