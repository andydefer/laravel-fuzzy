# Pint Formatting Test Report
*Generated: lun. 12 janv. 2026 07:31:22 WAT*


  ⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯..⨯.⨯.⨯.⨯⨯⨯⨯.⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ................................................................................................................................................. 70 files, 61 style issues  
  ⨯ config/fuzzy.php                                                                                                                                                   ordered_imports  
  ⨯ database/migrations/2024_01_01_000000_create_fuzzy_index_table.php                                      class_definition, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ rector.php                                                                                                                                                            concat_space  
  ⨯ src/Commands/ClearCacheCommand.php                                                                                                 concat_space, not_operator_with_successor_space  
  ⨯ src/Commands/ClearIndexCommand.php                                                                                                 not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Commands/IndexSearchCommand.php new_with_parentheses, function_declaration, single_quote, concat_space, not_operator_with_successor_space, blank_line_before_statement, phpdo…  
  ⨯ src/Commands/StatsIndexCommand.php                                                                                 function_declaration, concat_space, blank_line_before_statement  
  ⨯ src/Contracts/MustFuzzySearch.php                                                                                                        class_attributes_separation, phpdoc_align  
  ⨯ src/Data/FuzzySearchableData.php                                                                                             braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/IndexConfigData.php                                                                                                 braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchOptionsData.php                                                                                               braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchResultData.php                                                                                     not_operator_with_successor_space, ordered_imports, phpdoc_align  
  ⨯ src/Exceptions/FuzzySearchException.php                                                                                                                               phpdoc_align  
  ⨯ src/Exceptions/ModelNotSearchableException.php                                                                                                                        phpdoc_align  
  ⨯ src/FuzzySearch.php                                                                                                                                                ordered_imports  
  ⨯ src/FuzzySearchServiceProvider.php                                                                       new_with_parentheses, function_declaration, concat_space, ordered_imports  
  ⨯ src/Models/FuzzyIndex.php                                                                                                                            ordered_imports, phpdoc_align  
  ⨯ src/Repositories/IndexRepository.php                                               concat_space, phpdoc_separation, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/SearchContext.php        concat_space, phpdoc_separation, statement_indentation, not_operator_with_successor_space, blank_line_before_statement, ordered_imports, phpdoc_align  
  ⨯ src/Services/AdvancedScoringCalculator.php                                                                              increment_style, blank_line_before_statement, phpdoc_align  
  ⨯ src/Services/Algorithms/LongestCommonSubstringAlgorithm.php                                                                           increment_style, blank_line_before_statement  
  ⨯ src/Services/Algorithms/PrefixSimilarityAlgorithm.php                                                                                                              increment_style  
  ⨯ src/Services/FuzzySearchService.php new_with_parentheses, function_declaration, concat_space, braces_position, not_operator_with_successor_space, single_line_empty_body, no_extr…  
  ⨯ src/Services/IndexBuilder.php                                                                                                braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringEngine.php                       function_declaration, phpdoc_separation, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/ExactMatchStrategy.php                                                 braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ src/Services/Scoring/ScoringStrategies/FuzzyMatchStrategy.php                                           braces_position, not_operator_with_successor_space, single_line_empty_body  
  ⨯ src/Services/SimilarityCalculator.php                                                          new_with_parentheses, increment_style, blank_line_before_statement, ordered_imports  
  ⨯ src/Services/StringNormalizer.php                                                                            function_declaration, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Stages/MatchDiscoveryStage.php function_declaration, increment_style, concat_space, phpdoc_separation, cast_spaces, not_operator_with_successor_space, blank_line_before_stat…  
  ⨯ src/Stages/NormalizeQueryStage.php                                                                                                                   ordered_imports, phpdoc_align  
  ⨯ src/Stages/ScoringStage.php                                                                                                       phpdoc_separation, ordered_imports, phpdoc_align  
  ⨯ src/Stages/SortAndLimitStage.php                                                                                                                function_declaration, phpdoc_align  
  ⨯ src/Traits/FuzzySearchable.php                                                                                                           class_attributes_separation, phpdoc_align  
  ⨯ src/ValueObjects/IndexData.php                                                    concat_space, braces_position, single_line_empty_body, blank_line_before_statement, phpdoc_align  
  ⨯ tests/Feature/ArtisanCommandsTest.php                                                                                 increment_style, single_quote, concat_space, ordered_imports  
  ⨯ tests/Feature/CommandsTest.php class_attributes_separation, function_declaration, no_multiline_whitespace_around_double_arrow, increment_style, single_quote, concat_space, no_ex…  
  ⨯ tests/Feature/ConfigurationTest.php                                                                                     concat_space, trailing_comma_in_multiline, ordered_imports  
  ⨯ tests/Feature/FacadeTest.php                                                                                                                         concat_space, ordered_imports  
  ⨯ tests/Feature/IntegrationTest.php                                                     class_attributes_separation, increment_style, concat_space, braces_position, ordered_imports  
  ⨯ tests/Feature/MonitoringTest.php                                                                                                    increment_style, concat_space, ordered_imports  
  ⨯ tests/Feature/ShouldBeIndexedTest.php                                                                         new_with_parentheses, concat_space, braces_position, ordered_imports  
  ⨯ tests/Fixtures/Product.php                                                                                                                                         ordered_imports  
  ⨯ tests/Fixtures/User.php                                                                                                                concat_space, ordered_imports, phpdoc_align  
  ⨯ tests/TestCase.php                                                                                                                     concat_space, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/CacheTest.php                                                                                                               single_quote, concat_space, ordered_imports  
  ⨯ tests/Unit/FuzzySearchServiceTest.php                                                 increment_style, concat_space, phpdoc_single_line_var_spacing, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Models/FuzzyIndexTest.php                                                                                           new_with_parentheses, concat_space, ordered_imports  
  ⨯ tests/Unit/Repositories/IndexRepositoryTest.php                                class_attributes_separation, new_with_parentheses, concat_space, no_unused_imports, ordered_imports  
  ⨯ tests/Unit/Services/AdvancedScoringCalculatorTest.php                                                                                        new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/IndexBuilderTest.php                                         class_attributes_separation, new_with_parentheses, concat_space, braces_position, ordered_imports  
  ⨯ tests/Unit/Services/ScoringEngineTest.php                                                                                                    new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/SimilarityCalculatorTest.php                                                                                             new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/StringNormalizerTest.php                                                                           new_with_parentheses, no_extra_blank_lines, ordered_imports  
  ⨯ tests/Unit/Stages/MatchDiscoveryStageTest.php                                             new_with_parentheses, function_declaration, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/NormalizeQueryStageTest.php                                             new_with_parentheses, function_declaration, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/ScoringStageTest.php                                                    new_with_parentheses, function_declaration, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/SortAndLimitStageTest.php                                             new_with_parentheses, function_declaration, increment_style, concat_space, ordered_imports  
  ⨯ tests/Unit/ValueObjects/SearchQueryTest.php                                                                                                  new_with_parentheses, ordered_imports  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_products_table.php                                                                   new_with_parentheses, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_users_table.php                                                                      new_with_parentheses, braces_position  

