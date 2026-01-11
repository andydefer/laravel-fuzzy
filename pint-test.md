# Pint Formatting Test Report
*Generated: dim. 11 janv. 2026 12:31:00 WAT*


  ⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯..⨯.⨯⨯.⨯⨯⨯.⨯.⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ................................................................................................................................................. 51 files, 43 style issues  
  ⨯ config/fuzzy.php                                                                                                                                              no_extra_blank_lines  
  ⨯ database/migrations/2024_01_01_000000_create_fuzzy_index_table.php                                      class_definition, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ rector.php                                                                                                                                                            concat_space  
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
  ⨯ src/FuzzySearchServiceProvider.php       new_with_parentheses, function_declaration, no_multiline_whitespace_around_double_arrow, concat_space, no_unused_imports, ordered_imports  
  ⨯ src/Models/FuzzyIndex.php                                                                                   no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Repositories/IndexRepository.php                                               concat_space, no_unused_imports, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/SearchContext.php                                                                                    class_attributes_separation, blank_line_before_statement, ordered_imports  
  ⨯ src/Services/AdvancedScoringCalculator.php        control_structure_braces, braces_position, statement_indentation, not_operator_with_successor_space, blank_line_before_statement  
  ⨯ src/Services/Algorithms/LongestCommonSubstringAlgorithm.php                                                                                            blank_line_before_statement  
  ⨯ src/Services/FuzzySearchService.php class_attributes_separation, new_with_parentheses, function_declaration, no_superfluous_phpdoc_tags, concat_space, phpdoc_separation, phpdoc_…  
  ⨯ src/Services/IndexBuilder.php                                                                    no_superfluous_phpdoc_tags, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ExactMatchStrategy.php                                                                   braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ src/Services/Scoring/FuzzyMatchStrategy.php                                                             braces_position, not_operator_with_successor_space, single_line_empty_body  
  ⨯ src/Services/Scoring/ScoringStrategy.php                                                                                                               class_attributes_separation  
  ⨯ src/Services/Scoring/UnifiedScoringOrchestrator.php                                                                                        function_declaration, no_unused_imports  
  ⨯ src/Services/SimilarityCalculator.php                                            class_attributes_separation, new_with_parentheses, no_unused_imports, blank_line_before_statement  
  ⨯ src/Services/StringNormalizer.php                                                                            function_declaration, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Stages/ExactMatchStage.php                                                                                    concat_space, not_operator_with_successor_space, ordered_imports  
  ⨯ src/Stages/FuzzyMatchStage.php                    no_superfluous_phpdoc_tags, concat_space, not_operator_with_successor_space, no_extra_blank_lines, ordered_imports, phpdoc_align  
  ⨯ src/Stages/MultiWordConsolidationStage.php                    control_structure_braces, braces_position, statement_indentation, not_operator_with_successor_space, ordered_imports  
  ⨯ src/Stages/NormalizeQueryStage.php                                                                                                                   ordered_imports, phpdoc_align  
  ⨯ src/Stages/SortAndLimitStage.php                                                                            function_declaration, no_unused_imports, ordered_imports, phpdoc_align  
  ⨯ src/Stages/UnifiedScoringStage.php function_declaration, concat_space, braces_position, not_operator_with_successor_space, single_line_empty_body, blank_line_before_statement, o…  
  ⨯ src/Stages/WordMatchStage.php                                                                 concat_space, no_unused_imports, no_extra_blank_lines, ordered_imports, phpdoc_align  
  ⨯ src/Traits/FuzzySearchable.php                                                                            no_superfluous_phpdoc_tags, phpdoc_trim, no_unused_imports, phpdoc_align  
  ⨯ src/ValueObjects/IndexData.php                                                                  concat_space, braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ tests/Fixtures/Product.php                                                                                                                                         ordered_imports  
  ⨯ tests/Fixtures/User.php                                                                                     no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/TestCase.php                                                                            no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/FuzzySearchServiceTest.php                                                         no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_products_table.php                          new_with_parentheses, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_users_table.php                             new_with_parentheses, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  

