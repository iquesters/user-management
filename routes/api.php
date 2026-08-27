<?php

/**
 * Legacy auth API endpoints were intentionally removed from this route file.
 *
 * The supported guest auth endpoints now live in `routes/auth.php`, where they
 * run inside the `web` middleware stack and can safely use session state for
 * OTP verification and registration completion.
 */
