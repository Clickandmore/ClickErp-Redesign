#!/usr/bin/env bash

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if [[ ! -f .gitmodules ]]; then
    echo "Missing .gitmodules. Nothing to back up."
    exit 0
fi

timestamp="$(date +%Y%m%d-%H%M%S)"
backup_root="$repo_root/.package-backups/$timestamp"
mkdir -p "$backup_root"

submodules=()
while IFS= read -r line; do
    submodules+=("${line##* }")
done < <(git config --file .gitmodules --get-regexp '^submodule\..*\.path$' || true)

if [[ ${#submodules[@]} -eq 0 ]]; then
    echo "No submodules found. Nothing to back up."
    exit 0
fi

for path in "${submodules[@]}"; do
    [[ -d "$path" ]] || continue

    package_name="$(basename "$path")"
    package_dir="$backup_root/$package_name"
    mkdir -p "$package_dir"

    package_head="$(git -C "$path" rev-parse HEAD 2>/dev/null || echo "unknown")"
    package_branch="$(git -C "$path" symbolic-ref --quiet --short HEAD 2>/dev/null || echo "DETACHED")"

    {
        echo "path=$path"
        echo "head=$package_head"
        echo "branch=$package_branch"
        echo "created_at=$timestamp"
    } > "$package_dir/metadata.txt"

    git -C "$path" status --short > "$package_dir/status.txt" || true
    git -C "$path" diff --binary > "$package_dir/working.diff" || true
    git -C "$path" diff --cached --binary > "$package_dir/staged.diff" || true
    git -C "$path" log --decorate --graph --oneline -n 20 > "$package_dir/log.txt" || true
    git -C "$path" bundle create "$package_dir/$package_name.bundle" --all HEAD >/dev/null 2>&1 || true
    git -C "$path" ls-files --others --exclude-standard > "$package_dir/untracked.txt" || true

    if [[ -s "$package_dir/untracked.txt" ]]; then
        tar -czf "$package_dir/untracked.tar.gz" -C "$path" -T "$package_dir/untracked.txt"
    fi
done

echo "Package backup saved in $backup_root"
