#!/usr/bin/env bash

set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

submodule_paths=()
if [[ -f .gitmodules ]]; then
    while IFS=' ' read -r _ submodule_path; do
        submodule_paths+=("$submodule_path")
    done < <(git config --file .gitmodules --get-regexp '^submodule\..*\.path$' || true)
fi

is_submodule_path() {
    local candidate="$1"

    for submodule_path in "${submodule_paths[@]}"; do
        if [[ "$submodule_path" == "$candidate" ]]; then
            return 0
        fi
    done

    return 1
}

errors=()

while IFS= read -r package_dir; do
    [[ -f "$package_dir/composer.json" ]] || continue

    package_top_level="$(git -C "$package_dir" rev-parse --show-toplevel 2>/dev/null || true)"

    if is_submodule_path "$package_dir"; then
        expected_top_level="$repo_root/$package_dir"
        if [[ "$package_top_level" != "$expected_top_level" ]]; then
            errors+=("$package_dir is configured as a submodule, but its local git metadata is incomplete.")
            continue
        fi

        package_status="$(git -C "$package_dir" status --porcelain)"
        if [[ -n "$package_status" ]]; then
            errors+=("$package_dir has uncommitted changes. Commit them inside the package repository first.")
        fi

        package_head="$(git -C "$package_dir" rev-parse HEAD 2>/dev/null || true)"
        root_gitlink="$(git ls-files -s -- "$package_dir" | awk '{print $2}')"
        if [[ -n "$package_head" && -n "$root_gitlink" && "$package_head" != "$root_gitlink" ]]; then
            errors+=("$package_dir points to $root_gitlink in the main repository, but the package HEAD is $package_head. Run 'git add $package_dir' and commit the updated submodule pointer in the main repository.")
        fi

        remote_contains_head="$(git -C "$package_dir" branch -r --contains "$package_head" | sed '/^[[:space:]]*$/d' || true)"
        if [[ -z "$remote_contains_head" ]]; then
            errors+=("$package_dir HEAD commit $package_head is not present on any remote branch. Push the package repository first.")
        fi

        continue
    fi

    if [[ "$package_top_level" == "$repo_root" ]]; then
        package_root_status="$(git status --porcelain -- "$package_dir")"
        if [[ -n "$package_root_status" ]]; then
            errors+=("$package_dir has changes, but it is part of the main repository and not a dedicated package repository. Those changes cannot be pushed to a separate package repo in the current setup.")
        fi
        continue
    fi

    if [[ "$package_top_level" == "$repo_root/$package_dir" ]]; then
        nested_status="$(git -C "$package_dir" status --porcelain)"
        if [[ -n "$nested_status" ]]; then
            errors+=("$package_dir is a standalone git repository but is not registered in .gitmodules. Commit and push it explicitly, then update the main repository intentionally.")
        fi
    fi
done < <(find packages -mindepth 1 -maxdepth 1 -type d | sort)

if [[ ${#errors[@]} -gt 0 ]]; then
    echo "Push blocked because package repositories still need attention:"
    printf ' - %s\n' "${errors[@]}"
    exit 1
fi

echo "Package repository check passed."
