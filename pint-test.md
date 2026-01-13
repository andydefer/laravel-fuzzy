# Pint Formatting Test Report
*Generated: mar. 13 janv. 2026 21:34:55 WAT*


  ⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯.⨯⨯⨯.⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ................................................................................................................................................. 77 files, 73 style issues  
  ⨯ config/fuzzy-defaults.php                                                                                                                                          ordered_imports  
  ⨯ database/migrations/2024_01_01_000000_create_fuzzy_index_table.php                                      class_definition, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ rector.php                                                                                                                                                            concat_space  
  ⨯ src/Commands/ClearCacheCommand.php                           no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/Commands/ClearIndexCommand.php                                                        no_superfluous_phpdoc_tags, phpdoc_trim, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Commands/IndexSearchCommand.php new_with_parentheses, function_declaration, single_quote, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_…  
  ⨯ src/Commands/StatsIndexCommand.php                                                no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, blank_line_before_statement, phpdoc_align  
  ⨯ src/Contracts/IndexRepositoryInterface.php                                                                                                                            phpdoc_align  
  ⨯ src/Contracts/MustFuzzySearch.php                                                                                                                                     phpdoc_align  
  ⨯ src/Contracts/ScoreCalculatorInterface.php                                                                                                                            phpdoc_align  
  ⨯ src/Contracts/SimilarityAlgorithmInterface.php                                                                                                                        phpdoc_align  
  ⨯ src/Data/FuzzySearchableData.php                                                                                             braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/IndexConfigData.php                                                                                                 braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchOptionsData.php                                                                                               braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchResultData.php                no_superfluous_phpdoc_tags, braces_position, not_operator_with_successor_space, single_line_empty_body, ordered_imports, phpdoc_align  
  ⨯ src/Exceptions/FuzzySearchException.php                                                                                                                               phpdoc_align  
  ⨯ src/Exceptions/ModelNotSearchableException.php                                                                                                                        phpdoc_align  
  ⨯ src/FuzzySearch.php                                                                                                                                                ordered_imports  
  ⨯ src/FuzzySearchServiceProvider.php new_with_parentheses, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, o…  
  ⨯ src/Models/FuzzyIndex.php                                                                                                                            ordered_imports, phpdoc_align  
  ⨯ src/Repositories/IndexRepository.php  new_with_parentheses, no_superfluous_phpdoc_tags, concat_space, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/SearchContext.php  phpdoc_no_package, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, ordered_imports,…  
  ⨯ src/Services/AdvancedScoringCalculator.php                                                                              increment_style, blank_line_before_statement, phpdoc_align  
  ⨯ src/Services/Algorithms/LevenshteinSimilarityAlgorithm.php                                                                                                            phpdoc_align  
  ⨯ src/Services/Algorithms/LongestCommonSubstringAlgorithm.php                                                             increment_style, blank_line_before_statement, phpdoc_align  
  ⨯ src/Services/Algorithms/PrefixSimilarityAlgorithm.php                                                                                                increment_style, phpdoc_align  
  ⨯ src/Services/Algorithms/WordSimilarityComparator.php                     class_attributes_separation, trailing_comma_in_multiline, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Services/FuzzySearchService.php new_with_parentheses, function_declaration, no_superfluous_phpdoc_tags, concat_space, braces_position, phpdoc_separation, phpdoc_trim, not_op…  
  ⨯ src/Services/IndexBuilder.php                                                                    no_superfluous_phpdoc_tags, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringEngine.php                                          function_declaration, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/ExactMatchStrategy.php                                                                braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/FuzzyMatchStrategy.php                             braces_position, not_operator_with_successor_space, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/MultiWordStrategy.php                                                                          phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/WordMatchStrategy.php                                                                 braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategy.php                                                                                                                              phpdoc_align  
  ⨯ src/Services/SimilarityCalculator.php                                            new_with_parentheses, increment_style, blank_line_before_statement, ordered_imports, phpdoc_align  
  ⨯ src/Services/StringNormalizer.php                                                                            function_declaration, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Stages/MatchDiscoveryStage.php class_attributes_separation, function_declaration, increment_style, concat_space, not_operator_with_successor_space, blank_line_before_stateme…  
  ⨯ src/Stages/NormalizeQueryStage.php                                                                          new_with_parentheses, phpdoc_separation, ordered_imports, phpdoc_align  
  ⨯ src/Stages/RelevanceScoringStage.php                                                            braces_position, single_line_empty_body, blank_line_before_statement, phpdoc_align  
  ⨯ src/Stages/ScoringStage.php                                                                                                                          ordered_imports, phpdoc_align  
  ⨯ src/Stages/SortAndLimitStage.php                                                                                                                function_declaration, phpdoc_align  
  ⨯ src/Traits/FuzzySearchable.php                                                                                                                                        phpdoc_align  
  ⨯ src/ValueObjects/IndexData.php                                                    concat_space, braces_position, single_line_empty_body, blank_line_before_statement, phpdoc_align  
  ⨯ src/ValueObjects/SearchQuery.php                                                                                             braces_position, single_line_empty_body, phpdoc_align  
  ⨯ tests/Feature/ArtisanCommandsTest.php                                                                                 increment_style, single_quote, concat_space, ordered_imports  
  ⨯ tests/Feature/CommandsTest.php                              function_declaration, increment_style, single_quote, concat_space, php_unit_method_casing, trailing_comma_in_multiline  
  ⨯ tests/Feature/ConfigurationTest.php                                                                                     concat_space, trailing_comma_in_multiline, ordered_imports  
  ⨯ tests/Feature/FacadeTest.php                                                                                                                         concat_space, ordered_imports  
  ⨯ tests/Feature/IntegrationTest.php                                        increment_style, no_superfluous_phpdoc_tags, concat_space, braces_position, ordered_imports, phpdoc_align  
  ⨯ tests/Feature/MonitoringTest.php                                                                                                    increment_style, concat_space, ordered_imports  
  ⨯ tests/Feature/ShouldBeIndexedTest.php                                                             class_attributes_separation, new_with_parentheses, concat_space, braces_position  
  ⨯ tests/Fixtures/Product.php                                                                                                                 no_superfluous_phpdoc_tags, phpdoc_trim  
  ⨯ tests/Fixtures/User.php                                                                       no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/TestCase.php                                                                                                                     concat_space, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/CacheTest.php                                                                                                     no_superfluous_phpdoc_tags, concat_space, phpdoc_trim  
  ⨯ tests/Unit/FuzzySearchServiceTest.php                                                 increment_style, concat_space, phpdoc_single_line_var_spacing, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Models/FuzzyIndexTest.php                                                 new_with_parentheses, no_superfluous_phpdoc_tags, concat_space, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Repositories/IndexRepositoryTest.php                                                                              new_with_parentheses, concat_space, no_unused_imports  
  ⨯ tests/Unit/Services/AdvancedScoringCalculatorTest.php                                                           class_attributes_separation, new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/IndexBuilderTest.php                                                                                       new_with_parentheses, concat_space, braces_position  
  ⨯ tests/Unit/Services/ScoringEngineTest.php                                                                       class_attributes_separation, new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/SimilarityCalculatorTest.php                                                                                                              new_with_parentheses  
  ⨯ tests/Unit/Services/StringNormalizerTest.php                                                                                                                  new_with_parentheses  
  ⨯ tests/Unit/Services/WordSimilarityComparatorTest.php                                                 class_attributes_separation, new_with_parentheses, single_quote, concat_space  
  ⨯ tests/Unit/Stages/MatchDiscoveryStageTest.php    new_with_parentheses, function_declaration, no_superfluous_phpdoc_tags, phpdoc_trim, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/NormalizeQueryStageTest.php                                                              new_with_parentheses, function_declaration, blank_line_before_statement  
  ⨯ tests/Unit/Stages/RelevanceScoringStageTest.php                                       class_attributes_separation, new_with_parentheses, concat_space, blank_line_before_statement  
  ⨯ tests/Unit/Stages/ScoringStageTest.php                                    new_with_parentheses, function_declaration, no_unused_imports, blank_line_before_statement, phpdoc_align  
  ⨯ tests/Unit/Stages/SortAndLimitStageTest.php                                             new_with_parentheses, function_declaration, increment_style, concat_space, ordered_imports  
  ⨯ tests/Unit/ValueObjects/SearchQueryTest.php                                                                                                                   new_with_parentheses  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_products_table.php                                                                   new_with_parentheses, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_users_table.php                                                                      new_with_parentheses, braces_position  

