# Migrating from Bitbucket to GitHub

This guide will help you migrate your repository from Bitbucket to GitHub.

## Current Situation

Your repository is currently configured with a remote pointing to Bitbucket:
```
origin  https://chiheb_hadjamor@bitbucket.org/datapowa/dpcrm.git (fetch)
origin  https://chiheb_hadjamor@bitbucket.org/datapowa/dpcrm.git (push)
```

When you try to push to GitHub, you're getting an authentication error because your Git remote is still pointing to Bitbucket.

## Solution

### 1. Create a GitHub Repository

First, create a new repository on GitHub:

1. Go to [GitHub](https://github.com/) and sign in to your account.
2. Click on the "+" icon in the top-right corner and select "New repository".
3. Enter a name for your repository (e.g., "dpcrm").
4. Optionally, add a description for your repository.
5. Choose whether the repository should be public or private.
6. Do NOT initialize the repository with a README, .gitignore, or license as your project already has these files.
7. Click "Create repository".

### 2. Change the Remote URL

You have two options:

#### Option A: Update the existing 'origin' remote

If you want to replace the Bitbucket remote with GitHub:

```bash
git remote set-url origin https://github.com/yourusername/dpcrm.git
```

Replace `yourusername` with your GitHub username and `dpcrm` with the name of your repository.

#### Option B: Add GitHub as a new remote

If you want to keep both Bitbucket and GitHub remotes:

```bash
git remote add github https://github.com/yourusername/dpcrm.git
```

Then, to push to GitHub:

```bash
git push -u github master
```

### 3. Verify the Remote URL

To confirm that the remote URL has been updated correctly:

```bash
git remote -v
```

### 4. Push to GitHub

Now you can push your code to GitHub:

```bash
git push -u origin master
```

Or, if you added GitHub as a new remote:

```bash
git push -u github master
```

### 5. Authentication

If this is your first time pushing to GitHub, you might be prompted to authenticate. GitHub now recommends using personal access tokens instead of passwords for authentication:

1. Go to GitHub Settings > Developer settings > Personal access tokens
2. Generate a new token with appropriate permissions (repo, workflow, etc.)
3. Use this token instead of your password when prompted

### Using SSH Instead of HTTPS

If you prefer to use SSH instead of HTTPS for GitHub authentication:

1. Set up SSH keys if you haven't already: [GitHub SSH Key Setup](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent)
2. Change the remote URL:
   ```bash
   git remote set-url origin git@github.com:yourusername/dpcrm.git
   ```
   Or, if you added GitHub as a new remote:
   ```bash
   git remote set-url github git@github.com:yourusername/dpcrm.git
   ```

## Conclusion

After following these steps, your repository should be successfully migrated from Bitbucket to GitHub. You can now push and pull from your GitHub repository.
