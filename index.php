<?php
/**
 * DDEV Development Environment Landing Page
 * nr_saml_auth - TYPO3 SAML Authentication Extension
 *
 * @author Netresearch DTT GmbH
 */

// Get git information
$gitBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null') ?? 'unknown');
$gitCommit = trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?? 'unknown');
$gitRemote = trim(shell_exec('git config --get remote.origin.url 2>/dev/null') ?? '');

// Extract GitHub repo info for PR link
$prUrl = '';
$repoUrl = '';
if (preg_match('/github\.com[:\\/]([^\\/]+)\\/([^\\/]+?)(?:\\.git)?$/', $gitRemote, $matches)) {
    $owner = $matches[1];
    $repo = $matches[2];
    $repoUrl = "https://github.com/{$owner}/{$repo}";
    if ($gitBranch !== 'main' && $gitBranch !== 'master') {
        // Check if there's an open PR for this branch
        $prUrl = "{$repoUrl}/pulls?q=head:{$gitBranch}";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nr_saml_auth - DDEV Development Environment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --nr-turquoise: #2F99A4;
            --nr-turquoise-dark: #267a83;
            --nr-orange: #FF4D00;
            --nr-anthracite: #585961;
            --nr-grey: #CCCDCC;
            --nr-white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--nr-white);
            min-height: 100vh;
            color: var(--nr-anthracite);
            padding: 2rem;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }

        /* Header with Netresearch branding */
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--nr-turquoise);
        }
        .logo {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }
        .logo svg {
            width: 100%;
            height: 100%;
        }
        h1 {
            font-family: 'Raleway', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--nr-turquoise);
        }
        .subtitle {
            color: var(--nr-anthracite);
            font-size: 1rem;
            margin-top: 0.25rem;
        }

        /* Git info banner */
        .git-info {
            background: linear-gradient(135deg, var(--nr-turquoise) 0%, var(--nr-turquoise-dark) 100%);
            color: var(--nr-white);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .git-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .git-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.8;
        }
        .git-info-value {
            font-family: 'Raleway', monospace;
            font-weight: 600;
        }
        .git-info a {
            color: var(--nr-white);
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.5);
        }
        .git-info a:hover {
            border-bottom-color: var(--nr-white);
        }

        /* Credentials box */
        .credentials-box {
            background: #f8f9fa;
            border-left: 4px solid var(--nr-turquoise);
            border-radius: 0 8px 8px 0;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .credentials-box h3 {
            font-family: 'Raleway', sans-serif;
            font-weight: 600;
            color: var(--nr-turquoise);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .credentials-box code {
            background: var(--nr-white);
            border: 1px solid var(--nr-grey);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
        }

        /* Grid layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Cards */
        .card {
            background: var(--nr-white);
            border: 1px solid var(--nr-grey);
            border-radius: 8px;
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(47, 153, 164, 0.15);
        }
        .card h2 {
            font-family: 'Raleway', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--nr-anthracite);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card a {
            display: block;
            color: var(--nr-turquoise);
            text-decoration: none;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--nr-grey);
            transition: color 0.2s;
        }
        .card a:last-child { border-bottom: none; }
        .card a:hover { color: var(--nr-orange); }
        .card a span {
            color: var(--nr-anthracite);
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* Badge */
        .badge {
            display: inline-block;
            background: var(--nr-orange);
            color: var(--nr-white);
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Commands box */
        .commands-box {
            background: var(--nr-anthracite);
            color: var(--nr-white);
            border-radius: 8px;
            padding: 1.5rem;
        }
        .commands-box h3 {
            font-family: 'Raleway', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .commands-box code {
            background: rgba(255,255,255,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            color: var(--nr-turquoise);
        }
        .commands-box p {
            line-height: 2;
        }

        /* Footer */
        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--nr-grey);
            text-align: center;
            font-size: 0.85rem;
            color: var(--nr-anthracite);
            opacity: 0.7;
        }
        .footer a {
            color: var(--nr-turquoise);
            text-decoration: none;
        }
        .footer a:hover {
            color: var(--nr-orange);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <svg viewBox="-75 -75 440 440" xmlns="http://www.w3.org/2000/svg">
                    <title>Netresearch DTT GmbH</title>
                    <g>
                        <path fill="#2999a4" d="M209.6,0V31.62h32.77a26.38,26.38,0,0,1,26.44,26.43V242a26.38,26.38,0,0,1-26.44,26.44H209.6V300h47.93a42.77,42.77,0,0,0,42.86-42.86V42.89A42.76,42.76,0,0,0,257.53,0ZM43.25,0A42.76,42.76,0,0,0,.39,42.89V257.18A42.76,42.76,0,0,0,43.25,300H91.18V268.46H58.4A26.38,26.38,0,0,1,32,242v-184A26.37,26.37,0,0,1,58.4,31.62H91.18V0Z" transform="translate(-0.39 -0.04)"/>
                        <path fill="#595a62" d="M221.44,120.41c0-34.48-13.94-57.82-48.93-57.82-26.62,0-48.54,7.74-64.17,26.56l-.7-22.06-28.31.06V232.94h31.59V124.69c7.14-18.38,32.14-34.8,53-34.5,27.38.4,25.2,26.24,26,45.81v96.94h31.58" transform="translate(-0.39 -0.04)"/>
                    </g>
                </svg>
            </div>
            <div>
                <h1>nr_saml_auth</h1>
                <p class="subtitle">TYPO3 SAML Authentication Extension</p>
            </div>
        </div>

        <div class="git-info">
            <div class="git-info-item">
                <span class="git-info-label">Branch</span>
                <span class="git-info-value">
                    <?php if ($repoUrl): ?>
                        <a href="<?= htmlspecialchars($repoUrl) ?>/tree/<?= htmlspecialchars($gitBranch) ?>" target="_blank"><?= htmlspecialchars($gitBranch) ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($gitBranch) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="git-info-item">
                <span class="git-info-label">Commit</span>
                <span class="git-info-value">
                    <?php if ($repoUrl): ?>
                        <a href="<?= htmlspecialchars($repoUrl) ?>/commit/<?= htmlspecialchars($gitCommit) ?>" target="_blank"><?= htmlspecialchars($gitCommit) ?></a>
                    <?php else: ?>
                        <?= htmlspecialchars($gitCommit) ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($prUrl && $gitBranch !== 'main' && $gitBranch !== 'master'): ?>
            <div class="git-info-item">
                <span class="git-info-label">PR</span>
                <span class="git-info-value">
                    <a href="<?= htmlspecialchars($prUrl) ?>" target="_blank">View PRs</a>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <div class="credentials-box">
            <h3>Backend Credentials</h3>
            <p>Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>Joh316!</code></p>
        </div>

        <div class="grid">
            <div class="card">
                <h2>TYPO3 12.4 LTS</h2>
                <a href="https://v12.nr-saml-auth.ddev.site/">
                    Frontend <span>v12.nr-saml-auth.ddev.site</span>
                </a>
                <a href="https://v12.nr-saml-auth.ddev.site/typo3/">
                    Backend <span>/typo3/</span>
                </a>
            </div>

            <div class="card">
                <h2>TYPO3 13.4 LTS <span class="badge">Latest</span></h2>
                <a href="https://v13.nr-saml-auth.ddev.site/">
                    Frontend <span>v13.nr-saml-auth.ddev.site</span>
                </a>
                <a href="https://v13.nr-saml-auth.ddev.site/typo3/">
                    Backend <span>/typo3/</span>
                </a>
            </div>

            <div class="card">
                <h2>Documentation</h2>
                <a href="https://docs.nr-saml-auth.ddev.site/">
                    Local Docs <span>Run: ddev docs</span>
                </a>
                <?php if ($repoUrl): ?>
                <a href="<?= htmlspecialchars($repoUrl) ?>" target="_blank">
                    GitHub <span>Source repository</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="commands-box">
            <h3>Quick Commands</h3>
            <p>
                <code>make up</code> Start DDEV + install all TYPO3 versions<br>
                <code>make test</code> Run tests &nbsp;|&nbsp;
                <code>make lint</code> Check code style &nbsp;|&nbsp;
                <code>make ci</code> Full CI pipeline
            </p>
        </div>

        <div class="footer">
            <a href="https://www.netresearch.de/" target="_blank">Netresearch DTT GmbH</a>
        </div>
    </div>
</body>
</html>
