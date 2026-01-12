# Rector Refactoring Report
*Generated: lun. 12 janv. 2026 14:10:53 WAT*


2 files with changes
====================

1) /home/andy-kani/pro/sites/packages/laravel-fuzzy/src/FuzzySearchServiceProvider.php:78

    ---------- begin diff ----------
@@ @@
                     } else {
                         $merged[$key] = $defaultValue;
                     }
+
                     break;

                 case 'scoring':
@@ @@

                 default:
                     // Fusion standard pour les autres sections
-                    if (is_array($defaultValue) && is_array($userValue)) {
-                        $merged[$key] = array_merge($defaultValue, $userValue);
-                    } else {
-                        $merged[$key] = $userValue;
-                    }
+                    $merged[$key] = is_array($defaultValue) && is_array($userValue) ? array_merge($defaultValue, $userValue) : $userValue;
             }
         }

@@ @@
                     break;

                 default:
-                    if (is_array($defaultValue) && is_array($userValue)) {
-                        $merged[$key] = array_merge($defaultValue, $userValue);
-                    } else {
-                        $merged[$key] = $userValue;
-                    }
+                    $merged[$key] = is_array($defaultValue) && is_array($userValue) ? array_merge($defaultValue, $userValue) : $userValue;
             }
         }
    ----------- end diff -----------

Applied rules:
 * SimplifyIfElseToTernaryRector
 * NewlineAfterStatementRector


2) /home/andy-kani/pro/sites/packages/laravel-fuzzy/tests/Unit/Repositories/IndexRepositoryTest.php:10

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


 [OK] 2 files would have been changed (dry-run) by Rector                                                               

