# Pint Formatting Test Report
*Generated: mar. 16 juin 2026 09:25:35 WAT*


  ⨯⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯...⨯⨯⨯⨯⨯.⨯⨯⨯.⨯⨯⨯.⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯⨯..⨯⨯⨯

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FAIL   ............................................................................................................................................... 158 files, 146 style issues  
  ⨯ database/migrations/2024_01_01_000000_create_fuzzy_index_table.php                                      class_definition, no_superfluous_phpdoc_tags, braces_position, phpdoc_trim  
  ⨯ rector.php                                                                                                                              fully_qualified_strict_types, concat_space  
  ⨯ src/Commands/ClearCacheCommand.php        phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/Commands/ClearIndexCommand.php                                     phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Commands/IndexSearchCommand.php new_with_parentheses, single_quote, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_befo…  
  ⨯ src/Commands/StatsIndexCommand.php                             phpdoc_no_package, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, blank_line_before_statement, phpdoc_align  
  ⨯ src/Config/AdvancedScoringConfig.php                                                                                         braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Config/BaseSimilarityConfig.php                                                                        phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body  
  ⨯ src/Config/CacheConfig.php                                                                                                   concat_space, braces_position, single_line_empty_body  
  ⨯ src/Config/CoverageBonusConfig.php               no_superfluous_phpdoc_tags, no_trailing_whitespace_in_comment, braces_position, phpdoc_trim, single_line_empty_body, phpdoc_align  
  ⨯ src/Config/LevenshteinAlgorithmConfig.php                                                                              class_attributes_separation, phpdoc_no_package, phpdoc_trim  
  ⨯ src/Config/LongestCommonSubstringConfig.php                                                                            class_attributes_separation, phpdoc_no_package, phpdoc_trim  
  ⨯ src/Config/PrefixAlgorithmConfig.php                                                                                   class_attributes_separation, phpdoc_no_package, phpdoc_trim  
  ⨯ src/Config/SimilarityCalculatorConfig.php                                                           class_attributes_separation, phpdoc_no_package, phpdoc_separation, phpdoc_trim  
  ⨯ src/Config/WordSimilarityComparatorConfig.php                                 class_attributes_separation, phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body  
  ⨯ src/Contracts/CacheManagerInterface.php                                                                   phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/CacheStoreInterface.php                                                                                                                                 phpdoc_align  
  ⨯ src/Contracts/ConfigInterface.php                                                                                                          no_superfluous_phpdoc_tags, phpdoc_trim  
  ⨯ src/Contracts/ContextualNormalizerInterface.php                                                                                       phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/IndexManagerInterface.php                                                                   phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/IndexRepositoryInterface.php                                                                             fully_qualified_strict_types, ordered_imports, phpdoc_align  
  ⨯ src/Contracts/ModelDiscoveryInterface.php                                              phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_separation, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/MustFuzzySearch.php                                                                                                                   phpdoc_no_package, phpdoc_trim  
  ⨯ src/Contracts/PipelineManagerInterface.php                                                                                            phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/ResultFilterInterface.php                                                                                               phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/ScoreCalculatorInterface.php                                                                                            phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/ScoringEngineInterface.php                                                                                              phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/SearchContextInterface.php                                                                  phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/SearchProcessorInterface.php                                                                                            phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/SearchServiceInterface.php phpdoc_no_package, fully_qualified_strict_types, no_superfluous_phpdoc_tags, no_trailing_whitespace_in_comment, phpdoc_trim, ordered_imp…  
  ⨯ src/Contracts/SimilarityAlgorithmInterface.php                                                                                        phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/StageInterface.php                                                                                                      phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Contracts/StringNormalizerInterface.php                                                                                           phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Data/FuzzySearchableData.php                                                             phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/IndexConfigData.php                                                                 phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchOptionsData.php                                                               phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body, phpdoc_align  
  ⨯ src/Data/SearchResultData.php                                               phpdoc_no_package, braces_position, phpdoc_trim, single_line_empty_body, ordered_imports, phpdoc_align  
  ⨯ src/Enums/IndexationLevel.php                                                                                                                                         phpdoc_align  
  ⨯ src/Enums/StageType.php                                                                                                                             phpdoc_no_package, phpdoc_trim  
  ⨯ src/Exceptions/DuplicateStageException.php                                                                                                              concat_space, phpdoc_align  
  ⨯ src/Exceptions/FuzzySearchException.php                                                                                               phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/Exceptions/ModelNotSearchableException.php                                                                                        phpdoc_no_package, phpdoc_trim, phpdoc_align  
  ⨯ src/FuzzySearch.php                                                                            fully_qualified_strict_types, phpdoc_separation, no_unused_imports, ordered_imports  
  ⨯ src/FuzzySearchServiceProvider.php                                                  fully_qualified_strict_types, concat_space, no_trailing_whitespace_in_comment, ordered_imports  
  ⨯ src/Models/FuzzyIndex.php                                                                                            phpdoc_no_package, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Repositories/IndexRepository.php new_with_parentheses, phpdoc_no_package, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, phpdoc_a…  
  ⨯ src/SearchContext.php  phpdoc_no_package, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, ordered_imports,…  
  ⨯ src/Services/AdvancedScoringCalculator.php                          increment_style, no_trailing_whitespace_in_comment, blank_line_before_statement, ordered_imports, phpdoc_align  
  ⨯ src/Services/Algorithms/LevenshteinSimilarityAlgorithm.php                                                           phpdoc_no_package, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Services/Algorithms/LongestCommonSubstringAlgorithm.php                                         increment_style, phpdoc_no_package, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Services/Algorithms/PrefixSimilarityAlgorithm.php                                               increment_style, phpdoc_no_package, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Services/Algorithms/WordSimilarity/LetterDistanceCalculator.php                                                 trailing_comma_in_multiline, not_operator_with_successor_space  
  ⨯ src/Services/Algorithms/WordSimilarity/WordMatchScorer.php                                                                               class_attributes_separation, phpdoc_align  
  ⨯ src/Services/Algorithms/WordSimilarity/WordSimilarityCalculator.php                                   class_attributes_separation, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Services/Algorithms/WordSimilarityComparator.php class_attributes_separation, phpdoc_no_package, nullable_type_declaration_for_default_null_value, phpdoc_trim, blank_line_be…  
  ⨯ src/Services/CacheManagerService.php                    class_attributes_separation, concat_space, not_operator_with_successor_space, blank_line_before_statement, ordered_imports  
  ⨯ src/Services/FuzzySearchService.php function_declaration, phpdoc_no_package, fully_qualified_strict_types, braces_position, phpdoc_trim, single_line_empty_body, blank_line_befor…  
  ⨯ src/Services/IndexBuilder.php                                 phpdoc_no_package, fully_qualified_strict_types, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, phpdoc_align  
  ⨯ src/Services/IndexManagerService.php new_with_parentheses, no_trailing_whitespace_in_comment, braces_position, phpdoc_separation, not_operator_with_successor_space, single_line_…  
  ⨯ src/Services/ModelDiscoveryService.php class_attributes_separation, new_with_parentheses, phpdoc_no_package, concat_space, phpdoc_separation, phpdoc_trim, not_operator_with_succ…  
  ⨯ src/Services/PipelineManagerService.php                function_declaration, no_trailing_whitespace_in_comment, phpdoc_separation, not_operator_with_successor_space, phpdoc_align  
  ⨯ src/Services/PipelineStageManager.php            function_declaration, braces_position, phpdoc_separation, not_operator_with_successor_space, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/ResultFilterService.php                                                                                                                          function_declaration  
  ⨯ src/Services/Scoring/ScoringEngine.php function_declaration, no_multiline_whitespace_around_double_arrow, no_superfluous_phpdoc_tags, no_trailing_whitespace_in_comment, phpdoc_t…  
  ⨯ src/Services/Scoring/ScoringStrategies/ExactMatchStrategy.php                             no_trailing_whitespace_in_comment, braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategies/FuzzyMatchStrategy.php no_trailing_whitespace_in_comment, braces_position, not_operator_with_successor_space, single_line_empty_body, phpd…  
  ⨯ src/Services/Scoring/ScoringStrategies/MultiWordStrategy.php                                                     phpdoc_no_package, no_trailing_whitespace_in_comment, phpdoc_trim  
  ⨯ src/Services/Scoring/ScoringStrategies/WordMatchStrategy.php                                                                 braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/Services/Scoring/ScoringStrategyInterface.php                                                                                  no_trailing_whitespace_in_comment, phpdoc_align  
  ⨯ src/Services/SearchProcessorService.php function_declaration, fully_qualified_strict_types, braces_position, single_line_empty_body, blank_line_before_statement, ordered_imports…  
  ⨯ src/Services/ServiceRegistrar.php                                     new_with_parentheses, function_declaration, concat_space, not_operator_with_successor_space, ordered_imports  
  ⨯ src/Services/SimilarityCalculator.php                  class_attributes_separation, increment_style, no_unused_imports, blank_line_before_statement, ordered_imports, phpdoc_align  
  ⨯ src/Services/StringNormalizer.php class_attributes_separation, function_declaration, phpdoc_no_package, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, not_operator_with_…  
  ⨯ src/Stages/MatchDiscoveryStage.php                                                                                              not_operator_with_successor_space, ordered_imports  
  ⨯ src/Stages/MatchDiscoveryStage/IndexOptimizer.php           class_attributes_separation, function_declaration, increment_style, not_operator_with_successor_space, ordered_imports  
  ⨯ src/Stages/MatchDiscoveryStage/MatchFinder.php class_attributes_separation, increment_style, concat_space, not_operator_with_successor_space, blank_line_before_statement, ordere…  
  ⨯ src/Stages/NormalizeQueryStage.php                                                                                             new_with_parentheses, ordered_imports, phpdoc_align  
  ⨯ src/Stages/RelevanceScoringStage.php                                                                                    blank_line_before_statement, ordered_imports, phpdoc_align  
  ⨯ src/Stages/ScoringStage.php                                                                                                       phpdoc_separation, ordered_imports, phpdoc_align  
  ⨯ src/Stages/SortAndLimitStage.php                                                                                               function_declaration, ordered_imports, phpdoc_align  
  ⨯ src/StopWords/en.php                                                                                                                     single_quote, trailing_comma_in_multiline  
  ⨯ src/StopWords/fr.php                                                                                                                     single_quote, trailing_comma_in_multiline  
  ⨯ src/Traits/CommandHelpers.php             phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, not_operator_with_successor_space, blank_line_before_statement, phpdoc_align  
  ⨯ src/Traits/FuzzySearchable.php                                                           phpdoc_no_package, no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ src/Traits/ServiceProviderHelper.php                                                                                              nullable_type_declaration_for_default_null_value  
  ⨯ src/ValueObjects/IndexData.php                                                    concat_space, braces_position, single_line_empty_body, blank_line_before_statement, phpdoc_align  
  ⨯ src/ValueObjects/SearchQuery.php                                                                                             braces_position, single_line_empty_body, phpdoc_align  
  ⨯ src/helpers.php                                                                              blank_line_after_opening_tag, not_operator_with_successor_space, no_extra_blank_lines  
  ⨯ tests/Feature/ConfigurationTest.php                                                                                  concat_space, no_trailing_whitespace_in_comment, phpdoc_align  
  ⨯ tests/Feature/FacadeTest.php                                                                                            class_attributes_separation, concat_space, ordered_imports  
  ⨯ tests/Feature/IntegrationTest.php                                            increment_style, no_superfluous_phpdoc_tags, concat_space, phpdoc_trim, ordered_imports, phpdoc_align  
  ⨯ tests/Feature/MonitoringTest.php                                                   increment_style, concat_space, no_trailing_whitespace_in_comment, ordered_imports, phpdoc_align  
  ⨯ tests/Feature/ShouldBeIndexedTest.php                                                                                                                concat_space, braces_position  
  ⨯ tests/Fixtures/CreateAndUpdateUser.php                                                                                               fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/CreateOnlyUser.php                                                                                                    fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/CustomStage.php                                                 no_trailing_whitespace_in_comment, not_operator_with_successor_space, ordered_imports, phpdoc_align  
  ⨯ tests/Fixtures/CustomStage2.php                                                                                                 not_operator_with_successor_space, ordered_imports  
  ⨯ tests/Fixtures/DeleteOnlyUser.php                                                                                                    fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/NonIndexableCreateOnlyUser.php                                                                                        fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/NonIndexableUpdateOnlyUser.php                                                                                        fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/NonSearchableModel.php                                                                                               class_attributes_separation, no_unused_imports  
  ⨯ tests/Fixtures/NoneUser.php                                                                                                          fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/Product.php                                                                                                                 no_superfluous_phpdoc_tags, phpdoc_trim  
  ⨯ tests/Fixtures/UpdateOnlyUser.php                                                                                                    fully_qualified_strict_types, ordered_imports  
  ⨯ tests/Fixtures/User.php                                                                                                   no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports  
  ⨯ tests/Fixtures/UserSearchData.php                                                                                           no_superfluous_phpdoc_tags, concat_space, phpdoc_align  
  ⨯ tests/TestCase.php                                                                                                  concat_space, no_unused_imports, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Cache/LaravelCacheStoreTest.php                                                    new_with_parentheses, no_trailing_whitespace_in_comment, blank_line_before_statement  
  ⨯ tests/Unit/Commands/ClearCacheCommandTest.php                                                                                       class_attributes_separation, no_unused_imports  
  ⨯ tests/Unit/Commands/ClearIndexCommandTest.php                                                                                                   new_with_parentheses, concat_space  
  ⨯ tests/Unit/Commands/IndexSearchCommandTest.php new_with_parentheses, single_quote, fully_qualified_strict_types, concat_space, php_unit_method_casing, no_unused_imports, ordered…  
  ⨯ tests/Unit/Commands/StatsIndexCommandTest.php                                                                                                   new_with_parentheses, concat_space  
  ⨯ tests/Unit/FuzzySearchServiceProviderTest.php                                       fully_qualified_strict_types, concat_space, not_operator_with_successor_space, ordered_imports  
  ⨯ tests/Unit/FuzzySearchServiceTest.php class_attributes_separation, lambda_not_used_import, fully_qualified_strict_types, no_trailing_whitespace_in_comment, trailing_comma_in_mul…  
  ⨯ tests/Unit/Models/FuzzyIndexTest.php                                                 new_with_parentheses, no_superfluous_phpdoc_tags, concat_space, ordered_imports, phpdoc_align  
  ⨯ tests/Unit/Repositories/IndexRepositoryTest.php                                                                              new_with_parentheses, concat_space, no_unused_imports  
  ⨯ tests/Unit/Services/AdvancedScoringCalculatorTest.php                                                           class_attributes_separation, new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/CacheManagerServiceTest.php                               class_attributes_separation, new_with_parentheses, function_declaration, blank_line_before_statement  
  ⨯ tests/Unit/Services/IndexBuilderTest.php                                                                     new_with_parentheses, concat_space, no_trailing_whitespace_in_comment  
  ⨯ tests/Unit/Services/IndexManagerServiceTest.php                                                                                                        class_attributes_separation  
  ⨯ tests/Unit/Services/ModelDiscoveryServiceTest.php                                                                                          new_with_parentheses, no_unused_imports  
  ⨯ tests/Unit/Services/PipelineManagerServiceTest.php         class_attributes_separation, new_with_parentheses, braces_position, single_line_empty_body, blank_line_before_statement  
  ⨯ tests/Unit/Services/PipelineStageManagerTest.php                                                                                                   concat_space, no_unused_imports  
  ⨯ tests/Unit/Services/ResultFilterServiceTest.php                                                                                                               new_with_parentheses  
  ⨯ tests/Unit/Services/ScoringEngineTest.php                                                                       class_attributes_separation, new_with_parentheses, ordered_imports  
  ⨯ tests/Unit/Services/SearchProcessorServiceTest.php                                                class_attributes_separation, lambda_not_used_import, trailing_comma_in_multiline  
  ⨯ tests/Unit/Services/ServiceRegistrarTest.php class_attributes_separation, new_with_parentheses, function_declaration, fully_qualified_strict_types, concat_space, no_trailing_whi…  
  ⨯ tests/Unit/Services/StringNormalizerTest.php                                                                                                                  new_with_parentheses  
  ⨯ tests/Unit/Services/WordSimilarityComparatorTest.php                                                 class_attributes_separation, new_with_parentheses, single_quote, concat_space  
  ⨯ tests/Unit/Stages/MatchDiscoveryStage/IndexOptimizerTest.php                                                                                                  new_with_parentheses  
  ⨯ tests/Unit/Stages/MatchDiscoveryStage/MatchFinderTest.php class_attributes_separation, new_with_parentheses, single_line_after_imports, blank_line_before_statement, ordered_impo…  
  ⨯ tests/Unit/Stages/MatchDiscoveryStageTest.php class_attributes_separation, new_with_parentheses, function_declaration, fully_qualified_strict_types, single_line_after_imports, b…  
  ⨯ tests/Unit/Stages/NormalizeQueryStageTest.php                                                              new_with_parentheses, function_declaration, blank_line_before_statement  
  ⨯ tests/Unit/Stages/RelevanceScoringStageTest.php   class_attributes_separation, new_with_parentheses, concat_space, no_unused_imports, blank_line_before_statement, ordered_imports  
  ⨯ tests/Unit/Stages/ScoringStageTest.php                                    new_with_parentheses, function_declaration, no_unused_imports, blank_line_before_statement, phpdoc_align  
  ⨯ tests/Unit/Stages/SortAndLimitStageTest.php new_with_parentheses, function_declaration, increment_style, concat_space, no_unused_imports, blank_line_before_statement, ordered_im…  
  ⨯ tests/Unit/ValueObjects/SearchQueryTest.php                                                                                                                   new_with_parentheses  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_products_table.php                                                                   new_with_parentheses, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000000_create_test_users_table.php                                                                      new_with_parentheses, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000001_create_create_none_users_table.php                                 new_with_parentheses, blank_line_after_opening_tag, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000001_create_create_only_users_table.php                                 new_with_parentheses, blank_line_after_opening_tag, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000002_create_create_and_update_users_table.php                           new_with_parentheses, blank_line_after_opening_tag, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000003_create_update_only_users_table.php                                 new_with_parentheses, blank_line_after_opening_tag, braces_position  
  ⨯ tests/database/migrations/2024_01_01_000004_create_delete_only_users_table.php                                 new_with_parentheses, blank_line_after_opening_tag, braces_position  
  ⨯ tests/database/migrations/non_indexable_create_only_users.php                                                                                new_with_parentheses, braces_position  
  ⨯ tests/database/migrations/non_indexable_update_only_users.php                                                                                new_with_parentheses, braces_position  

