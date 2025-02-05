.PHONY: build test test81 test82 test83 test84

# This is a Makefile for running tests on different PHP versions using GitHub Actions.
# The `act` tool is used to run the tests locally.
# To install `act`, run `brew install act` on macOS.

YELLOW := \033[33m
GREEN := \033[32m
RED := \033[31m
NC := \033[0m

clean:
	@echo "${YELLOW}Cleaning up...${NC}"
	composer clean

format:
	@echo "${YELLOW}Formatting code...${NC}"
	composer format


format-check:
	@echo "${YELLOW}Checking formatting...${NC}"
	composer format

test:
	@echo "${GREEN}Running full test matrix...${NC}"
	act -j test

test81:
	@echo "${GREEN}Running tests on PHP 8.1...${NC}"
	act -j test --matrix php:8.1

test82:
	@echo "${GREEN}Running tests on PHP 8.2...${NC}"
	act -j test --matrix php:8.2

test83:
	@echo "${GREEN}Running tests on PHP 8.3...${NC}"
	act -j test --matrix php:8.3

test84:
	@echo "${GREEN}Running tests on PHP 8.4...${NC}"
	act -j test --matrix php:8.4
