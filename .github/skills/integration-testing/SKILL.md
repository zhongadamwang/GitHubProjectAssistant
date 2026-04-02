---
name: integration-testing
description: Execute comprehensive integration testing of all 30 EDPS skills with enhanced coverage for T07 workflow orchestrator, T08 completion gates, and T09 prompt pattern recognition. Validates end-to-end workflow archetypes, gate behavior, and classification accuracy to ensure seamless VS Code integration.
license: MIT
---

# Integration Testing Framework (Enhanced for T10)

## Intent
Orchestrate comprehensive testing and validation of the complete EDPS skill ecosystem with enhanced focus on Phase 3 deliverables: T07 workflow orchestrator, T08 completion gates, and T09 prompt pattern recognition. Execute end-to-end workflow archetype testing, validate gate schemas and behaviors, verify classification accuracy, and ensure complete system integration reliability.

**Enhanced for T10**: Comprehensive phase 4 testing framework validating T07+T08+T09 integration with workflow archetype scenarios, gate validation matrix, and prompt routing accuracy testing.

## Inputs
- **Skill Definitions**: `.github/skills/*/SKILL.md` (all 32 skills + orchestrator)
- **Gate Schemas**: `.github/skills/*/gate.json` (32 completion gate definitions)
- **Test Projects**: `projects/*/` (sample projects for workflow testing)
- **Sample Data**: `projects/*/artifacts/Sample Data/` (diverse test inputs)
- **Prompt Corpus**: T09 classification test prompts (240+ labeled examples)
- **Project State**: T07 project-state.json samples for persistence testing

## Outputs

**Enhanced Test Reports (T10)**:
- `projects/[test-project]/artifacts/Testing/integration-test-report.md` - Comprehensive human-readable report
- `projects/[test-project]/artifacts/Testing/integration-test-results.json` - Structured test data
- `projects/[test-project]/artifacts/Testing/workflow-archetype-tests.md` - T07 workflow testing results  
- `projects/[test-project]/artifacts/Testing/gate-validation-matrix.md` - T08 gate testing results
- `projects/[test-project]/artifacts/Testing/prompt-classification-accuracy.md` - T09 routing validation
- `projects/[test-project]/artifacts/Testing/state-persistence-validation.md` - Project state testing
- `projects/[test-project]/artifacts/Testing/phase3-integration-score.json` - Overall T07+T08+T09 score

### Enhanced JSON Structure (`integration-test-results.json`)
```json
{
  "test_execution": {
    "test_suite_id": "integration-test-t10-enhanced",
    "execution_timestamp": "ISO8601",
    "total_skills_tested": 32,
    "phase3_components": ["T07-orchestrator", "T08-gates", "T09-classification"],
    "test_environment": {
      "vscode_version": "string",
      "copilot_version": "string", 
      "os_platform": "Windows|Mac|Linux",
      "test_project": "project_name"
    },
    "execution_time_total": "seconds",
    "overall_status": "PASSED|FAILED|WARNING"
  },
  "phase3_integration_tests": {
    "t07_orchestrator_tests": {
      "workflow_archetype_tests": {
        "standard_workflow": {
          "status": "PASSED|FAILED",
          "execution_time": "seconds", 
          "dag_validation": "PASSED|FAILED",
          "state_persistence": "PASSED|FAILED",
          "prerequisite_enforcement": "PASSED|FAILED",
          "skill_sequence": ["requirements-ingest", "goals-extract", "..."]
        },
        "rapid_workflow": {
          "status": "PASSED|FAILED",
          "execution_time": "seconds",
          "bypass_optimization": "PASSED|FAILED", 
          "parallel_execution": "PASSED|FAILED"
        },
        "compliance_workflow": {
          "status": "PASSED|FAILED",
          "execution_time": "seconds",
          "documentation_completeness": "PASSED|FAILED",
          "audit_trail": "PASSED|FAILED"
        }
      },
      "project_state_tests": {
        "serialization": "PASSED|FAILED",
        "deseralization": "PASSED|FAILED", 
        "resume_accuracy": "PASSED|FAILED",
        "state_consistency": "PASSED|FAILED"
      },
      "dag_engine_tests": {
        "dependency_resolution": "PASSED|FAILED",
        "circular_dependency_detection": "PASSED|FAILED",
        "parallel_skill_identification": "PASSED|FAILED"
      }
    },
    "t08_gate_tests": {
      "gate_schema_validation": {
        "total_gates_tested": 32,
        "gates_passed": 30,
        "gates_failed": 2,
        "validation_results": [
          {
            "skill_name": "requirements-ingest",
            "gate_file": ".github/skills/requirements-ingest/gate.json", 
            "schema_valid": "PASSED|FAILED",
            "artifact_checks": "PASSED|FAILED",
            "content_checks": "PASSED|FAILED",
            "archetype_behavior": {
              "standard": "PASSED|FAILED",
              "rapid": "PASSED|FAILED", 
              "compliance": "PASSED|FAILED"
            }
          }
        ]
      },
      "gate_behavior_tests": {
        "hard_gate_blocking": "PASSED|FAILED",
        "soft_gate_warnings": "PASSED|FAILED", 
        "bypass_with_justification": "PASSED|FAILED",
        "quality_score_calculation": "PASSED|FAILED"
      },
      "integration_with_orchestrator": {
        "gate_evaluation_triggers": "PASSED|FAILED",
        "remediation_guidance": "PASSED|FAILED",
        "workflow_transitions": "PASSED|FAILED"
      }
    },
    "t09_classification_tests": {
      "accuracy_validation": {
        "total_prompts_tested": 240,
        "correct_classifications": 228,
        "accuracy_percentage": 95.0,
        "meets_target": "boolean",
        "high_confidence_accuracy": 98.2,
        "medium_confidence_accuracy": 91.5
      },
      "disambiguation_tests": {
        "ambiguous_prompts_tested": 15,
        "appropriate_questions_generated": 14,
        "disambiguation_success_rate": 93.3
      },
      "session_learning_tests": {
        "correction_capture": "PASSED|FAILED",
        "pattern_adaptation": "PASSED|FAILED",
        "subsequent_accuracy": "PASSED|FAILED"
      },
      "workflow_routing": {
        "single_skill_routing": "PASSED|FAILED",
        "archetype_detection": "PASSED|FAILED",
        "orchestrator_integration": "PASSED|FAILED"
      }
    }
  },
  "end_to_end_workflow_tests": [
    {
      "archetype": "standard_workflow",
      "test_scenario": "Complete EDPS project from requirements to documentation",
      "skills_executed": ["requirements-ingest", "goals-extract", "domain-extractconcepts", "diagram-generatecollaboration", "hierarchy-management", "documentation-automation", "hierarchy-validation", "edps-compliance"],
      "total_execution_time": "seconds",
      "gate_evaluations": 8,
      "gate_failures": 0, 
      "quality_score": 0.94,
      "status": "PASSED|FAILED",
      "classification_handoffs": [
        {
          "prompt": "Start comprehensive analysis",
          "classified_as": "standard_workflow", 
          "routed_to": "edps-workflow-orchestrator",
          "accuracy": "PASSED|FAILED"
        }
      ]
    }
  ],
  "legacy_skill_tests": [
    {
      "skill_name": "requirements-ingest",
      "test_status": "PASSED|FAILED|SKIPPED", 
      "execution_time": "seconds",
      "gate_Integration": {
        "gate_exists": "PASSED|FAILED",
        "gate_valid": "PASSED|FAILED",
        "gate_behavior": "PASSED|FAILED"
      },
      "orchestrator_integration": {
        "dag_compatible": "PASSED|FAILED",
        "completion_events": "PASSED|FAILED"
      },
      "classification_coverage": {
        "prompt_patterns": "PASSED|FAILED",
        "confidence_scoring": "PASSED|FAILED"
      }
    }
  ],
  "performance_analysis": {
    "overall_performance_rating": "EXCELLENT|GOOD|ACCEPTABLE|POOR",
    "phase3_performance": {
      "orchestrator_overhead": "seconds",
      "gate_evaluation_time": "seconds", 
      "classification_latency": "seconds"
    },
    "skills_meeting_standards": 32,
    "benchmark_results": {
      "fastest_workflow": {"archetype": "rapid", "time": "seconds"},
      "most_thorough": {"archetype": "compliance", "completeness": "percentage"}
    }
  },
  "integration_score": {
    "phase3_score": 0.95,
    "component_scores": {
      "t07_orchestrator": 0.96,
      "t08_gates": 0.93, 
      "t09_classification": 0.97
    },
    "overall_integration": "EXCELLENT|GOOD|ACCEPTABLE|POOR"
  }
}
```

## Enhanced Test Framework Architecture (T10)

### Phase 3 Component Testing

#### 1. T07 Workflow Orchestrator Testing  
**Purpose**: Validate complete workflow lifecycle management, DAG engine, and archetype behaviors

**Test Categories**:
- **Workflow Archetype Execution**
  - Standard workflow: Complete 12-skill sequence with balanced gates
  - Rapid workflow: Optimized 8-skill sequence with bypass behaviors
  - Compliance workflow: Enhanced 15-skill sequence with strict validation
- **DAG Prerequisite Engine**
  - Dependency resolution accuracy
  - Circular dependency detection  
  - Parallel skill identification and scheduling
- **Project State Management**
  - Serialization/deserialization accuracy
  - Cross-session resume functionality
  - State consistency during workflow transitions
- **Completion Events**
  - Event emission on skill completion
  - Integration with T08 gate evaluation
  - Error event handling and recovery

#### 2. T08 Completion Gate Testing
**Purpose**: Validate all 32 gate schemas and gate behavior across workflow archetypes

**Test Categories**:
- **Gate Schema Validation**
  - JSON schema compliance for all 32 gate.json files
  - Artifact existence checks with known-good/known-bad samples
  - Content pattern validation with realistic skill outputs
  - Quality score weighting calculation accuracy
- **Gate Behavior Testing** 
  - Hard gate blocking: Verify workflow stops on critical failures
  - Soft gate warnings: Verify workflow continues with warnings
  - Bypass authorization: Verify justification capture and audit logging
  - Archetype-specific behavior: standard/rapid/compliance differences
- **Integration with Orchestrator**
  - Gate evaluation triggers on `orchestrate complete`
  - Remediation guidance display and user interaction
  - Quality score aggregation and reporting

#### 3. T09 Classification Testing
**Purpose**: Validate ≥95% classification accuracy and intelligent routing

**Test Categories**:
- **Accuracy Validation**
  - 240+ prompt corpus testing with labeled expected results
  - High confidence (≥85%): Direct execution accuracy
  - Medium confidence (65-84%): Execution with alternatives
  - Low confidence (<65%): Appropriate disambiguation
- **Disambiguation Flow**
  - Ambiguous prompt detection (confidence gap <10%)
  - Question generation quality and clarity
  - User response handling and learning
- **Session Learning**
  - Correction capture and storage in project state
  - Pattern adaptation within conversation
  - Subsequent classification improvement
- **Workflow Integration**
  - Single skill vs. workflow archetype detection
  - Routing to T07 orchestrator for complex requests
  - Context awareness using project state

### End-to-End Workflow Archetype Testing

#### Standard Workflow Test Scenario
```
Test: Complete EDPS methodology execution
Duration: ~45 minutes expected
Skills: 12-core workflow sequence
Gates: All hard/soft gates active
Classification: "I need comprehensive requirements analysis and domain modeling"

Sequence:
1. requirements-ingest (with artifact gate)
2. goals-extract (with content gate)  
3. domain-extractconcepts (with entity gate)
4. diagram-generatecollaboration (with boundary gate)
5. hierarchy-management (with decomposition gate)
6. documentation-automation (with template gate)
7. hierarchy-validation (with structural gate)
8. edps-compliance (with methodology gate)

Validation:
- All gates pass with quality scores ≥80%
- Project state maintains consistency
- Traceability preserved across handoffs
- Classification routes correctly to workflow
```

#### Rapid Workflow Test Scenario  
```
Test: MVP-focused streamlined execution
Duration: ~20 minutes expected
Skills: 8-optimized sequence
Gates: Soft warnings only, bypass enabled
Classification: "Quick analysis for MVP prototype"

Sequence:
1. requirements-ingest (bypass mode)
2. goals-extract (essential only)
3. domain-extractconcepts (core entities)
4. diagram-generatecollaboration (basic interactions)
5. plan-derivetasks (MVP scope)

Validation:
- Execution time ≤50% of standard workflow
- Essential artifacts generated
- Quality gates in bypass/soft mode
- MVP-focused scope preserved
```

#### Compliance Workflow Test Scenario
```
Test: Audit-ready comprehensive execution  
Duration: ~90 minutes expected
Skills: 15-enhanced sequence with full documentation
Gates: All hard gates, enhanced documentation checks
Classification: "Prepare for regulatory compliance audit"

Sequence:
1-8. (Standard workflow core)
9. change-impact-analysis (risk documentation)
10. project-status-reporting (executive summary)
11. github-issue-create-update (audit trail)
12. orgmodel-update (institutional integration)

Validation:
- Complete audit documentation generated
- All gates hard-enforced with quality ≥95%
- Regulatory compliance artifacts present
- Executive reporting ready
```
### Test Suite Categories

#### 1. Individual Skill Testing
- **Input Validation**: Format checking, schema compliance, error handling
- **Processing Logic**: Core functionality validation, edge case handling
- **Output Validation**: JSON structure, markdown format, file generation
- **Performance Testing**: Execution time, memory usage, resource efficiency
- **Documentation Testing**: SKILL.md validation, example accuracy

#### 2. Skill Integration Testing
- **Data Flow**: Input/output compatibility between connected skills
- **Chain Validation**: Multi-skill workflow execution
- **Dependency Resolution**: Prerequisites and sequence validation
- **Error Propagation**: Failure handling across skill boundaries
- **State Management**: Workspace consistency during skill chains

#### 3. End-to-End Workflow Testing
- **Complete Pipelines**: Full requirements-to-schedule workflows
- **Real Data Testing**: Actual project scenarios and edge cases
- **Traceability Validation**: Reference chain integrity across all skills
- **Output Quality**: Final deliverable validation and coherence
- **Workflow Performance**: End-to-end execution timing and efficiency

#### 4. VS Code Integration Testing
- **Skill Loading**: Copilot skill discovery and initialization
- **Workspace Integration**: File system interactions and workspace structure
- **User Interface**: Prompt handling and response formatting
- **Error Handling**: VS Code error display and user feedback
- **Extension Compatibility**: Integration with other VS Code extensions

#### 5. Consistency & Standards Testing
- **Format Compliance**: Markdown, JSON, file naming standards
- **Cross-Skill Consistency**: Reference formats, terminology usage
- **Schema Validation**: JSON output structure compliance
- **Documentation Standards**: SKILL.md format and completeness
- **Traceability Format**: Reference link consistency across skills

## Enhanced Test Execution Methodology (T10)

### Phase 1: Phase 3 Component Validation
**Duration**: 30 minutes
**Focus**: Validate T07, T08, T09 individual component functionality

```javascript
// T07 Orchestrator Component Tests
function testWorkflowOrchestrator() {
  // Test 1: DAG prerequisite engine
  validateDAGResolution(sampleSkillDependencies);
  validateCircularDependencyDetection(cyclicScenario);
  
  // Test 2: Workflow archetype definitions
  validateWorkflowArchetype('standard', standardSkillSequence);
  validateWorkflowArchetype('rapid', rapidSkillSequence);  
  validateWorkflowArchetype('compliance', complianceSkillSequence);
  
  // Test 3: Project state management
  testStateSerialization(sampleProjectState);
  testStateDeserialization(serializedState);
  testCrossSessionResume(previousState);
  
  return {
    dagEngine: 'PASSED|FAILED',
    archetypes: 'PASSED|FAILED', 
    stateManagement: 'PASSED|FAILED'
  };
}

// T08 Gate Component Tests  
function testCompletionGates() {
  // Test 1: All 32 gate schema validation
  for (const gateSchema of getAllGateSchemas()) {
    validateGateSchema(gateSchema);
    testGateWithKnownGoodOutput(gateSchema);
    testGateWithKnownBadOutput(gateSchema);
  }
  
  // Test 2: Gate behavior validation
  testHardGateBlocking(criticalFailureScenario);
  testSoftGateWarning(minorFailureScenario);
  testBypassWithJustification(userOverrideScenario);
  
  // Test 3: Quality score calculation
  validateQualityScoreAggregation(multiGateScenario);
  
  return {
    schemaValidation: 'PASSED|FAILED',
    gateBehavior: 'PASSED|FAILED',
    qualityScoring: 'PASSED|FAILED'
  };
}

// T09 Classification Component Tests
function testPromptClassification() {
  // Test 1: Classification accuracy with corpus
  const promptCorpus = loadPromptCorpus(); // 240+ labeled examples
  const accuracy = validateClassificationAccuracy(promptCorpus);
  
  // Test 2: Disambiguation flow
  testDisambiguationGeneration(ambiguousPrompts);
  testUserCorrectionHandling(correctionScenarios);
  
  // Test 3: Session learning
  testPatternAdaptation(withinSessionLearning);
  testConfidenceCalibration(userFeedbackScenarios);
  
  return {
    overallAccuracy: accuracy, // Must be ≥95%
    disambiguationFlow: 'PASSED|FAILED',
    sessionLearning: 'PASSED|FAILED'
  };
}
```

### Phase 2: End-to-End Workflow Archetype Testing  
**Duration**: 90 minutes
**Focus**: Complete workflow scenarios with all integrations

```javascript
// Standard Workflow Integration Test
async function testStandardWorkflowIntegration() {
  // Setup: Fresh project state
  const projectState = initializeProjectState('standard');
  
  // Step 1: Classification routing
  const userPrompt = "I need comprehensive requirements analysis and domain modeling";
  const classification = await classifyPrompt(userPrompt);
  assert(classification.archetype === 'standard_workflow');
  
  // Step 2: Orchestrator workflow execution
  const workflow = await executeWorkflow(projectState, 'standard');
  
  // Step 3: Monitor gates throughout execution
  const gateResults = [];
  for (const skill of workflow.skillSequence) {
    const result = await executeSkillWithGates(skill, projectState);
    gateResults.push(result.gateEvaluation);
    
    // Verify gate decisions
    if (result.gateEvaluation.status === 'failed' && result.gateEvaluation.severity === 'hard') {
      assert(workflow.blocked === true);
    }
  }
  
  // Step 4: Validate final state
  validateWorkflowCompleteness(projectState);
  validateArtifactQuality(projectState.artifacts);
  validateTracabilityChain(projectState.requirements, projectState.finalOutputs);
  
  return {
    classificationAccurate: classification.confidence >= 0.85,
    workflowComplete: workflow.status === 'completed',
    gatesAllPassed: gateResults.every(g => g.status !== 'hard_failure'),
    qualityScore: calculateOverallQuality(gateResults)
  };
}

// Rapid Workflow Integration Test
async function testRapidWorkflowIntegration() {
  const userPrompt = "Quick analysis for MVP prototype";
  const startTime = Date.now();
  
  const classification = await classifyPrompt(userPrompt);
  assert(classification.archetype === 'rapid_workflow');
  
  const workflow = await executeWorkflow(projectState, 'rapid');
  const duration = Date.now() - startTime;
  
  // Rapid workflow should complete in ≤50% of standard time
  assert(duration <= STANDARD_WORKFLOW_TIME * 0.5);
  
  // Verify bypass behaviors
  const bypassed = workflow.gateResults.filter(g => g.status === 'bypassed');
  assert(bypassed.length >= 3); // Should bypass non-critical gates
  
  return {
    executionTime: duration,
    bypassBehavior: 'PASSED|FAILED',
    mvpFocus: 'PASSED|FAILED'
  };
}

// Compliance Workflow Integration Test  
async function testComplianceWorkflowIntegration() {
  const userPrompt = "Prepare for regulatory compliance audit";
  
  const classification = await classifyPrompt(userPrompt);
  assert(classification.archetype === 'compliance_workflow');
  
  const workflow = await executeWorkflow(projectState, 'compliance');
  
  // Verify enhanced documentation
  assert(workflow.artifacts.includes('audit-trail.md'));
  assert(workflow.artifacts.includes('executive-summary.md'));
  assert(workflow.artifacts.includes('regulatory-compliance-checklist.md'));
  
  // Verify all gates hard-enforced
  const hardGates = workflow.gateResults.filter(g => g.severity === 'hard');
  assert(hardGates.every(g => g.status === 'passed'));
  
  // Verify quality threshold ≥95%
  const overallQuality = calculateOverallQuality(workflow.gateResults);
  assert(overallQuality >= 0.95);
  
  return {
    auditReadiness: 'PASSED|FAILED',
    documentationComplete: 'PASSED|FAILED', 
    qualityThreshold: overallQuality
  };
}
```

### Phase 3: Error Condition and Edge Case Testing
**Duration**: 30 minutes  
**Focus**: Resilience and error handling validation

```javascript
// Error scenario testing
function testErrorHandling() {
  // Test 1: Missing prerequisite skills
  testBlockedSkillExecution(incompleteDAG);
  
  // Test 2: Gate hard failures
  testHardGateBlocking(missingRequiredArtifact);
  
  // Test 3: Classification edge cases
  testAmbiguousPromptHandling(edgeCasePrompts);
  testOutOfScopePrompts(nonEDPSRequests);
  
  // Test 4: State corruption recovery
  testMalformedStateRecovery(corruptedProjectState);
  
  // Test 5: Resource constraint handling  
  testMemoryLimitBehavior(largeSampleData);
  testTimeoutHandling(slowSkillExecution);
}
```

### Phase 4: Performance and Quality Benchmarking
**Duration**: 20 minutes
**Focus**: Performance standards and optimization validation

```javascript
function benchmarkPerformance() {
  const benchmarks = {
    t09_classification_latency: measureClassificationSpeed(samplePrompts),
    t08_gate_evaluation_time: measureGateEvaluationSpeed(allGateSchemas),
    t07_workflow_overhead: measureOrchestrationOverhead(standardWorkflow),
    end_to_end_performance: measureCompleteWorkflow(allArchetypes)
  };
  
  // Validate performance targets
  assert(benchmarks.t09_classification_latency < 500); // ms  
  assert(benchmarks.end_to_end_performance.standard < 45 * 60); // 45 minutes
  assert(benchmarks.end_to_end_performance.rapid < 20 * 60); // 20 minutes
  
  return benchmarks;
}
```

## Performance Standards & Validation

### Performance Criteria
- **Individual Skills**: < 1 minute execution time
- **Skill Chains**: < 5 minutes for 5-skill sequences
- **End-to-End**: < 15 minutes for complete requirements-to-schedule
- **Memory Usage**: < 500MB peak per skill execution
- **Error Rate**: < 1% for valid inputs
- **Format Compliance**: 100% for output validation

### Quality Assurance Metrics
- **Input Validation**: Handle malformed inputs gracefully
- **Output Consistency**: Maintain format standards across all skills
- **Traceability**: Preserve reference chains with 100% accuracy
- **Documentation**: Complete and accurate SKILL.md files
- **User Experience**: Intuitive prompts and clear error messages

## Test Data Management

### Sample Data Categories
1. **Requirements Documents**: Varied formats, complexity levels, domains
2. **Domain Content**: Technical, business, regulatory contexts
3. **Project Scenarios**: Small, medium, large-scale project simulations
4. **Edge Cases**: Malformed inputs, missing data, boundary conditions
5. **Integration Scenarios**: Multi-skill workflows, complex dependencies

### Test Environment Configuration
- **Isolated Testing**: Separate workspace for test execution
- **Baseline Measurement**: Clean environment performance baselines
- **Resource Monitoring**: CPU, memory, disk usage tracking
- **Error Logging**: Comprehensive error capture and analysis
- **Result Archival**: Test history and regression analysis

## Validation Reporting

### Automated Report Generation
- **Executive Summary**: High-level status and key metrics
- **Detailed Analysis**: Skill-by-skill performance and issues
- **Integration Assessment**: Workflow validation results
- **Performance Benchmarks**: Timing, resource usage, efficiency
- **Recommendations**: Prioritized improvement suggestions
- **Trend Analysis**: Performance changes over time

### Continuous Integration Support
- **Automated Testing**: Triggered on skill updates
- **Regression Detection**: Compare against baseline metrics
- **Quality Gates**: Pass/fail criteria for releases
- **Performance Monitoring**: Trend analysis and alerting
- **Documentation Updates**: Auto-generated test reports

## Error Handling & Recovery

### Error Classification
- **Critical**: Complete skill failure, data corruption
- **High**: Performance degradation, format violations
- **Medium**: Minor inconsistencies, documentation gaps
- **Low**: Style issues, optimization opportunities

### Recovery Procedures
- **Graceful Degradation**: Continue testing after individual failures
- **Rollback Capability**: Restore clean test environment
- **Error Isolation**: Prevent error propagation across tests
- **Root Cause Analysis**: Detailed failure investigation
- **Remediation Tracking**: Issue resolution monitoring

## Usage Examples

### Complete Integration Test
```markdown
# Execute full integration test suite
Use the integration-testing skill to run comprehensive validation of all 30 EDPS skills with the Banking Transactions sample project.

Skills under test (all 30):
- Requirements & Analysis: requirements-ingest, requirements-merge, goals-extract, process-w5h, process-merge, process-findtopandupdate, process-scopemin
- Domain Modeling: domain-extractconcepts, domain-alignentities, domain-proposenewconcepts, diagram-generatecollaboration, model-integration, hierarchy-management, documentation-automation, migration-tools
- Compliance & Validation: edps-compliance, hierarchy-validation, change-impact-analysis
- Planning & Management: project-document-management, project-planning-tracking, project-status-reporting, plan-derivetasks, plan-estimateeffort, plan-buildschedule, change-management, orgmodel-update
- Integration & Automation: github-issue-create-update, github-issue-sync-status
- Meta Skills: edps-skill-navigator, skill-creator

Expected outputs:
- Complete test report with pass/fail status for all 30 skills
- Performance analysis showing execution times and resource usage  
- Integration validation results for all skill chains
- VS Code compatibility assessment
- Recommendations for any identified issues
```

### Workflow-Specific Testing
```markdown
# Test specific workflow chains
Run integration testing for the full EDPS skill ecosystem using the AI Slowcooker project requirements.

**Requirements & Analysis chain:**
requirements-ingest → requirements-merge → goals-extract → process-w5h → process-merge → process-findtopandupdate → process-scopemin

**Domain Modeling chain:**
domain-extractconcepts → domain-alignentities → domain-proposenewconcepts → diagram-generatecollaboration → model-integration → hierarchy-management → hierarchy-validation → documentation-automation → migration-tools

**Compliance & Validation chain:**
edps-compliance → hierarchy-validation → change-impact-analysis

**Planning & Management chain:**
project-document-management → project-planning-tracking → plan-derivetasks → plan-estimateeffort → plan-buildschedule → process-scopemin → change-management → project-status-reporting → orgmodel-update

**Integration & Automation chain:**
github-issue-create-update → github-issue-sync-status

**Meta Skills:**
edps-skill-navigator → skill-creator

Focus areas:
- Data flow consistency across all handoffs within each category chain
- Cross-chain traceability preservation (requirements IDs flow through to tasks and schedules)
- Performance within acceptable standards for all 30 skills
```

### Performance Benchmarking
```markdown
# Performance validation focused test
Execute integration testing with emphasis on performance benchmarking for all skills using varied sample data sizes.

Performance criteria:
- Individual skills complete in < 1 minute
- Skill chains complete in < 5 minutes  
- Memory usage stays below 500MB per skill
- Generate performance optimization recommendations
```

### Regression Testing
```markdown
# Validate after skill updates
Run integration testing to validate system stability after updating the goals-extract and plan-derivetasks skills.

Regression checks:
- Ensure updated skills maintain backward compatibility
- Validate integration points with dependent skills
- Confirm performance hasn't degraded
- Check output format consistency is preserved
```