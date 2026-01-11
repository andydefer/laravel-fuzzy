# Pint Formatting Test Report
*Generated: dim. 11 janv. 2026 20:28:17 WAT*


  ⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯..⨯.⨯.⨯⨯⨯⨯.⨯.⨯⨯⨯⨯⨯.⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ................................................................................................................................................. 66 files, 57 style issues  
  ⨯ database/migrations/2024_01_01_000000_create_fuzzy_index_table.php                                      class_definition, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ rector.php                                                                                                                                                            concat_space  
  ⨯ src/Commands/ClearCacheCommand.php                                                        no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space  
  ⨯ src/Commands/ClearIndexCommand.php                                                        no_superfluous_phpdoc_tags, phpdoc_trim, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Commands/IndexSearchCommand.php new_with_parentheses, function_declaration, single_quote, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_…  
  ⨯ src/Commands/StatsIndexCommand.php                                                      function_declaration, no_superfluous_phpdoc_tags, phpdoc_trim, blank_line_before_statement  
  ⨯ src/Contracts/MustFuzzySearch.php                                                                                                                                     phpdoc_align  
  ⨯ src/Data/FuzzySearchableData.php                                                                 no_superfluous_phpdoc_tags, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/IndexConfigData.php                                                                                                 braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchOptionsData.php                                                                   no_superfluous_phpdoc_tags, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchResultData.php                                                                                     not_operator_with_successor_space, ordered_imports, phpdoc_align  
  ⨯ src/Exceptions/FuzzySearchException.php                                                                                                   no_superfluous_phpdoc_tags, phpdoc_align  
  ⨯ src/Exceptions/ModelNotSearchableException.php                                                                                                                        phpdoc_align  
  ⨯ src/FuzzySearch.php                                                                                                       no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports  
  ⨯ src/FuzzySearchServiceProvider.php                                                                       new_with_parentheses, function_declaration, concat_space, ordered_imports  
  ⨯ src/Models/FuzzyIndex.php                                                                                   no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Repositories/IndexRepository.php                                               concat_space, no_unused_imports, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/SearchContext.php                                   class_attributes_separation, concat_space, not_operator_with_successor_space, blank_line_before_statement, ordered_imports  
  ⨯ src/Services/AdvancedScoringCalculator.php                                                                                                             blank_line_before_statement  
  ⨯ src/Services/Algorithms/LongestCommonSubstringAlgorithm.php                                                                                            blank_line_before_statement  
  ⨯ src/Services/FuzzySearchService.php new_with_parentheses, function_declaration, concat_space, braces_position, no_unused_imports, not_operator_with_successor_space, single_line_…  
  ⨯ src/Services/IndexBuilder.php                                                                    no_superfluous_phpdoc_tags, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringEngine.php                                                        function_declaration, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/Services/Scoring/ScoringStrategies/ExactMatchStrategy.php                                                 braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ src/Services/Scoring/ScoringStrategies/FuzzyMatchStrategy.php                                           braces_position, not_operator_with_successor_space, single_line_empty_body  
  ⨯ src/Services/Scoring/ScoringStrategy.php                                                                                                               class_attributes_separation  
  ⨯ src/Services/SimilarityCalculator.php                                            class_attributes_separation, new_with_parentheses, no_unused_imports, blank_line_before_statement  
  ⨯ src/Services/StringNormalizer.php                                               function_declaration, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/Stages/MatchDiscoveryStage.php  class_attributes_separation, function_declaration, concat_space, cast_spaces, not_operator_with_successor_space, blank_line_before_statement,…  
  ⨯ src/Stages/NormalizeQueryStage.php                                                                                                                   ordered_imports, phpdoc_align  
  ⨯ src/Stages/ScoringStage.php                                                                                                     not_operator_with_successor_space, ordered_imports  
  ⨯ src/Stages/SortAndLimitStage.php                                                                            function_declaration, no_unused_imports, ordered_imports, phpdoc_align  
  ⨯ src/Traits/FuzzySearchable.php                                                                            no_superfluous_phpdoc_tags, phpdoc_trim, no_unused_imports, phpdoc_align  
  ⨯ src/ValueObjects/IndexData.php                                                                  concat_space, braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ tests/Feature/CommandsTest.php  class_attributes_separation, function_declaration, no_multiline_whitespace_around_double_arrow, single_quote, concat_space, no_extra_blank_lines,…  
  ⨯ tests/Feature/FacadeTest.php                                                                                            class_attributes_separation, concat_space, ordered_imports  
  ⨯ tests/Feature/IntegrationTest.php                                                                      class_attributes_separation, concat_space, braces_position, ordered_imports  
  ⨯ tests/Fixtures/Product.php                                                                                                                                         ordered_imports  
  ⨯ tests/Fixtures/User.php                                                                                     no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/TestCase.php                                                                            no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/CacheTest.php                                                                                                               single_quote, concat_space, ordered_imports  
  ⨯ tests/Unit/FuzzySearchServiceTest.php                         no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, phpdoc_single_line_var_spacing, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Models/FuzzyIndexTest.php                                                                                           new_with_parentheses, concat_space, ordered_imports  
  ⨯ tests/Unit/Repositories/IndexRepositoryTest.php                                class_attributes_separation, new_with_parentheses, concat_space, no_unused_imports, ordered_imports  
  ⨯ tests/Unit/Services/AdvancedScoringCalculatorTest.php                                                           class_attributes_separation, new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/IndexBuilderTest.php                                                   new_with_parentheses, concat_space, braces_position, no_unused_imports, ordered_imports  
  ⨯ tests/Unit/Services/ScoringEngineTest.php                                                    class_attributes_separation, new_with_parentheses, no_unused_imports, ordered_imports  
  ⨯ tests/Unit/Services/SimilarityCalculatorTest.php                                                                                             new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/StringNormalizerTest.php                                              class_attributes_separation, new_with_parentheses, no_extra_blank_lines, ordered_imports  
  ⨯ tests/Unit/Stages/MatchDiscoveryStageTest.php                                             new_with_parentheses, function_declaration, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/NormalizeQueryStageTest.php                                             new_with_parentheses, function_declaration, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/ScoringStageTest.php                                 new_with_parentheses, function_declaration, no_unused_imports, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/SortAndLimitStageTest.php                                                         new_with_parentheses, function_declaration, no_unused_imports, ordered_imports  
  ⨯ tests/Unit/ValueObjects/IndexDataTest.php                                                                                                              class_attributes_separation  
  ⨯ tests/Unit/ValueObjects/SearchQueryTest.php                                                                                                  new_with_parentheses, ordered_imports  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_products_table.php                          new_with_parentheses, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_users_table.php                             new_with_parentheses, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  

