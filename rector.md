# Rector Refactoring Report
*Generated: lun. 12 janv. 2026 08:38:01 WAT*


1 file with changes
===================

1) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Repositories/IndexRepositoryTest.php:10

    ---------- begin diff ----------
@@ @@
 use Fuzzy\Services\IndexBuilder;
 use Fuzzy\Services\Scoring\ScoringEngine;
 use Fuzzy\Services\SimilarityCalculator;
-use ReflectionMethod;
-use stdClass;
 use Fuzzy\Tests\TestCase;
 use Fuzzy\Repositories\IndexRepository;
 use Fuzzy\Models\FuzzyIndex;
    ----------- end diff -----------

Applied rules:


 [OK] 1 file would have been changed (dry-run) by Rector                                                                

