#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  ./scripts/package-attach-branches.sh
  ./scripts/package-attach-branches.sh --quiet

For detached submodules:
 - If HEAD matches a remote branch tip, attach it to a safe local branch that tracks that remote.
 - If HEAD is a local-only commit, create a wip branch so the commit cannot disappear on the next submodule update.
EOF
}

quiet=0
if [[ "${1:-}" == "--quiet" ]]; then
    quiet=1
    shift
fi

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

if [[ $# -gt 0 ]]; then
    usage
    exit 1
fi

log() {
    if [[ $quiet -eq 0 ]]; then
        echo "$*"
    fi
}

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if [[ ! -f .gitmodules ]]; then
    log "Missing .gitmodules. Nothing to attach."
    exit 0
fi

root_branch="$(git branch --show-current || true)"
timestamp="$(date +%Y%m%d-%H%M%S)"

submodules=()
while IFS= read -r line; do
    submodules+=("${line##* }")
done < <(git config --file .gitmodules --get-regexp '^submodule\..*\.path$' || true)

choose_remote_tip() {
    local path="$1"
    local head_sha="$2"
    local configured_branch="$3"
    local ref
    local refs=()

    while IFS= read -r ref; do
        [[ -n "$ref" ]] || continue
        refs+=("$ref")
    done < <(git -C "$path" for-each-ref --format='%(refname:short) %(objectname)' refs/remotes | awk -v head="$head_sha" '$2==head {print $1}')

    if [[ ${#refs[@]} -eq 0 ]]; then
        return 1
    fi

    if [[ -n "$configured_branch" ]]; then
        for ref in "${refs[@]}"; do
            if [[ "$ref" == */"$configured_branch" ]]; then
                echo "$ref"
                return 0
            fi
        done
    fi

    if [[ -n "$root_branch" ]]; then
        for ref in "${refs[@]}"; do
            if [[ "$ref" == */"$root_branch" ]]; then
                echo "$ref"
                return 0
            fi
        done
    fi

    for ref in "${refs[@]}"; do
        if [[ "$ref" != */HEAD ]]; then
            echo "$ref"
            return 0
        fi
    done

    echo "${refs[0]}"
}

for path in "${submodules[@]}"; do
    [[ -d "$path" ]] || continue

    current_branch="$(git -C "$path" symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
    if [[ -n "$current_branch" ]]; then
        log "$path already attached to $current_branch"
        continue
    fi

    package_name="$(basename "$path")"
    package_head="$(git -C "$path" rev-parse HEAD)"
    section_name="$(git config --file .gitmodules --get-regexp '^submodule\..*\.path$' | awk -v target="$path" '$2==target {sub(/\\.path$/, "", $1); print $1; exit}')"
    configured_branch="$(git config --file .gitmodules --get "$section_name.branch" 2>/dev/null || true)"
    remote_tip="$(choose_remote_tip "$path" "$package_head" "$configured_branch" || true)"

    if [[ -n "$remote_tip" ]]; then
        short_branch="${remote_tip##*/}"
        local_branch="submodule-${package_name}-${short_branch}"
        existing_branch_sha="$(git -C "$path" rev-parse --verify "$local_branch" 2>/dev/null || true)"

        if [[ "$existing_branch_sha" == "$package_head" ]]; then
            git -C "$path" switch "$local_branch" >/dev/null
        elif [[ -n "$existing_branch_sha" ]]; then
            local_branch="${local_branch}-${package_head:0:7}"
            if git -C "$path" rev-parse --verify "$local_branch" >/dev/null 2>&1; then
                git -C "$path" switch "$local_branch" >/dev/null
            else
                git -C "$path" switch -c "$local_branch" >/dev/null
            fi
            git -C "$path" branch --set-upstream-to="$remote_tip" "$local_branch" >/dev/null 2>&1 || true
        else
            git -C "$path" switch -c "$local_branch" >/dev/null
            git -C "$path" branch --set-upstream-to="$remote_tip" "$local_branch" >/dev/null 2>&1 || true
        fi

        log "$path attached to $local_branch tracking $remote_tip"
        continue
    fi

    local_branch="wip-${package_name}-${timestamp}"
    git -C "$path" switch -c "$local_branch" >/dev/null
    log "$path had a local detached commit. Anchored it on $local_branch"
done
