<div class="navbar">
    <div class="navbar-inner">
        <ul class="nav">
            <li<?php if ($tab == 'subscription'): ?> class="active"<?php endif ?>>
                <a href="/settings/billing">Subscription</a>
            </li>
            <li<?php if ($tab == 'statements'): ?> class="active"<?php endif ?>>
                <a href="/settings/billing/statements">Statements</a>
            </li>
        </ul>
    </div>
</div>