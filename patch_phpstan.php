<?php
$filepath = 'app/Modules/AdminKernel/Domain/Service/AuthorizationService.php';
$content = file_get_contents($filepath);

$old_block = <<<'OLD'
    /**
     * Checks if the user is granted at least one of the candidate permissions.
     */
    private function isGrantedAnyCandidate(int $adminId, array $candidates): bool
OLD;

$new_block = <<<'NEW'
    /**
     * Checks if the user is granted at least one of the candidate permissions.
     *
     * @param array<string> $candidates
     */
    private function isGrantedAnyCandidate(int $adminId, array $candidates): bool
NEW;

$content = str_replace($old_block, $new_block, $content);
file_put_contents($filepath, $content);
