#!/usr/bin/env bash

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
packages_dir="$repo_root/packages"

if [[ ! -d "$packages_dir" ]]; then
    exit 0
fi

has_nested_repo=0

for package in "$packages_dir"/*; do
    [[ -d "$package" ]] || continue

    if [[ -e "$package/.git" ]]; then
        has_nested_repo=1
        package_name="${package#$repo_root/}"

        if ! git -C "$package" diff --quiet || ! git -C "$package" diff --cached --quiet; then
            echo "Package repository has uncommitted changes: $package_name" >&2
            echo "Commit or stash those changes before pushing the main repository." >&2
            exit 1
        fi
    fi
done

if [[ "$has_nested_repo" -eq 0 ]]; then
    echo "No nested package Git repositories found; package check skipped."
fi
