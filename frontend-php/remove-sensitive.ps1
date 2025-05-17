# Script to remove sensitive files from git history
git filter-branch --force --index-filter `
"git rm --cached --ignore-unmatch private/config/env.php" `
--prune-empty --tag-name-filter cat -- --all

# Force push the changes
git push origin --force --all

# Clean up the local repository
git for-each-ref --format="delete %(refname)" refs/original/ | git update-ref --stdin
git reflog expire --expire=now --all
git gc --prune=now 