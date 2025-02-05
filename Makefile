.PHONY: build test test81 test82 test83 test84

YELLOW := \033[33m
GREEN := \033[32m
RED := \033[31m
NC := \033[0m # No Color

build:
	@echo "${GREEN}Building Docker images...${NC}"
	docker-compose build --no-cache

test: test81 test82 test83 test84

test81:
	@echo "${GREEN}Running tests on PHP 8.1...${NC}"
	docker-compose run --rm php81

test82:
	@echo "${GREEN}Running tests on PHP 8.2...${NC}"
	docker-compose run --rm php82

test83:
	@echo "${GREEN}Running tests on PHP 8.3...${NC}"
	docker-compose run --rm php83

test84:
	@echo "${GREEN}Running tests on PHP 8.4...${NC}"
	docker-compose run --rm php84

clean:
	@echo "${YELLOW}Cleaning up containers and images...${NC}"
	docker-compose down --rmi all --volumes --remove-orphans
