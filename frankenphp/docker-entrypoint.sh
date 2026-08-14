#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	check_writable_dir() {
		dir_path=$1
		if ! mkdir -p "$dir_path"; then
			echo "Cannot create directory: $dir_path"
			exit 1
		fi

		test_file="$dir_path/.permission-check-$$"
		if ! (umask 022 && : > "$test_file") 2>/dev/null; then
			echo "Directory is not writable: $dir_path"
			ls -ld "$dir_path" 2>/dev/null || true
			exit 1
		fi

		rm -f "$test_file"
	}

	echo 'Checking mounted volume permissions...'
	check_writable_dir /app/public/upload
	check_writable_dir /app/public/turnament
	check_writable_dir /app/public/turnament/csv

	# Display information about the current project
	# Or about an error in project initialization
	php bin/console -V

	echo 'Pruning PDF cache...'
	if ! php bin/console app:pdf-cache:cleanup --no-interaction; then
		echo 'PDF cache cleanup failed at startup; continuing.'
	fi

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

		echo 'Synchronizing Doctrine migration metadata...'
		php bin/console doctrine:migrations:sync-metadata-storage --no-interaction

		echo 'Applying pending database migrations...'
		set +e
		MIGRATION_OUTPUT=$(php bin/console doctrine:migrations:migrate --no-interaction --no-all-or-nothing 2>&1)
		MIGRATION_EXIT_CODE=$?
		set -e
		printf '%s\n' "$MIGRATION_OUTPUT"

		if [ "$MIGRATION_EXIT_CODE" -ne 0 ]; then
			if [ "$MIGRATION_EXIT_CODE" -ne 0 ]; then
				if printf '%s\n' "$MIGRATION_OUTPUT" | grep -Eq 'SQLSTATE\[(42P07|42701)\]|already exists'; then
					echo 'Schema already exists but migration metadata is behind; marking migrations as executed...'
					php bin/console doctrine:migrations:version --add --all --no-interaction
				else
					echo 'Migration failed with a non-recoverable error.'
					exit "$MIGRATION_EXIT_CODE"
				fi
			fi
		fi
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
