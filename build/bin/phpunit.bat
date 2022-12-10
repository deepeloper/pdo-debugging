@cd "../.."
@echo ------------------------------------------------------
@rem call php "vendor/bin/phpunit" -c "build/config/phpunit.xml" --log-junit "build/logs/junit.xml" -v --debug
@call php "vendor/bin/phpunit" -c "build/config/phpunit.xml" --log-junit "build/logs/junit.xml"
