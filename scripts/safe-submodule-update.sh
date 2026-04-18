#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  ./scripts/safe-submodule-update.sh
  ./scripts/safe-submodule-update.sh --remote

The script refuses to update if the root repository or any submodule has
uncommitted or untracked changes, so package work cannot be overwritten silently.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

update_args=(--init --recursive)
if [[ "${1:-}" == "--remote" ]]; then
    update_args+=(--remote)
elif [[ $# -gt 0 ]]; then
    usage
    exit 1
fi

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if [[ ! -f .gitmodules ]]; then
    echo "Missing .gitmodules. Refusing to continue because submodule metadata is incomplete."
    exit 1
fi

submodules=()
while IFS= read -r line; do
    submodules+=("${line##* }")
done < <(git config --file .gitmodules --get-regexp '^submodule\..*\.path$')

if [[ ${#submodules[@]} -eq 0 ]]; then
    echo "No submodules were found in .gitmodules."
    exit 1
fi

check_clean() {
    local label="$1"
    local path="$2"
    local status

    status="$(git -C "$path" status --porcelain)"
    if [[ -n "$status" ]]; then
        echo "Refusing to update because $label has pending changes:"
        echo "$status"
        exit 1
    fi
}

check_clean "the main repository" "$repo_root"

for path in "${submodules[@]}"; do
    if [[ ! -d "$path" ]]; then
        echo "Submodule path is missing: $path"
        exit 1
    fi

    check_clean "submodule $path" "$repo_root/$path"
done

git submodule sync --recursive
git submodule update "${update_args[@]}"

echo "Submodules are synced and clean."
git submodule status --recursive
