# Decomposition Patterns

## Table of Contents
1. [Participant Inference for New Sub-Processes](#participant-inference-for-new-sub-processes)
2. [Pattern A: Service / Orchestrator Decomposition](#pattern-a-service--orchestrator-decomposition)
3. [Pattern B: Engine / Processor Decomposition](#pattern-b-engine--processor-decomposition)
4. [Pattern C: Multi-Boundary Decomposition](#pattern-c-multi-boundary-decomposition)
5. [Level-by-Level Worked Examples](#level-by-level-worked-examples)
6. [Naming Conventions](#naming-conventions)

---

## Participant Inference for New Sub-Processes

When the user does not specify what participants to add inside the new sub-process, infer them from:

1. **Messages in the parent diagram involving the participant** — any message sent to or from the participant becomes a clue about what logic it encapsulates
2. **Participant name heuristics** — see table below
3. **Domain concepts** — if `domain-concepts.json` or `requirements.json` is available, search for entities related to the participant's name
4. **Direct user specification** — always prefer explicit user input over inference

### Name Heuristic Table

| Participant Name Contains | Likely Boundary Entry Point | Likely Controls | Likely Entities |
|--------------------------|----------------------------|-----------------|-----------------|
| `Service`, `Manager` | `[Name]API` or `[Name]Interface` | `[Name]Processor`, `[Name]Coordinator` | `[Name]Repository`, `[Name]Store` |
| `Engine`, `Processor` | `[Name]Orchestrator` | `[Name]Rules`, `[Name]Executor` | `[Name]State`, `[Name]Log` |
| `Gateway`, `Proxy` | `[Name]Endpoint` | `[Name]Router`, `[Name]Transformer` | `[Name]Cache`, `[Name]Audit` |
| `Platform`, `System` | `[Name]API` | Multiple domain services | Domain data stores |
| `Controller` | `[Name]Dispatcher` | `[Name]Handler`, `[Name]Validator` | `[Name]Registry` |

Prefer 3–5 participants total in a new sub-process for clarity. Add more only if the domain clearly warrants it.

---

## Pattern A: Service / Orchestrator Decomposition

**When**: The decomposed participant's name ends in `Service`, `Manager`, or contains orchestration semantics.

**Sub-process structure:**

```mermaid
sequenceDiagram
    %% Stereotypes: Parent=actor | API=boundary, Validator=control, Processor=control, Repository=entity
    participant Parent as "[Parent System]"

    box "[ServiceName] Boundary"
        participant API as "[Service] API"
        participant Validator as "[Service] Validator"
        participant Processor as "[Service] Processor"
        participant Repository as "[Service] Repository"
    end

    Parent->>API: [Service Request]
    API->>Validator: Validate [Input]
    Validator->>Processor: Process [Valid Input]
    Processor->>Repository: [Store/Retrieve Data]
    Repository-->>Processor: [Data Response]
    Processor-->>API: [Processing Result]
    API-->>Parent: [Service Response]
```

---

## Pattern B: Engine / Processor Decomposition

**When**: The decomposed participant's name ends in `Engine`, `Processor`, `Executor`, or `Rules`.

**Sub-process structure:**

```mermaid
sequenceDiagram
    %% Stereotypes: Parent=actor | Orchestrator=boundary, Rules=control, Executor=control, StateStore=entity
    participant Parent as "[Parent System]"

    box "[EngineName] Boundary"
        participant Orchestrator as "[Engine] Orchestrator"
        participant Rules as "Business Rules"
        participant Executor as "Task Executor"
        participant StateStore as "State Store"
    end

    Parent->>Orchestrator: Execute [Task]
    Orchestrator->>Rules: Apply Business Rules
    Rules->>Executor: Execute Validated Task
    Executor->>StateStore: Persist State
    StateStore-->>Orchestrator: State Confirmed
    Orchestrator-->>Parent: [Execution Result]
```

---

## Pattern C: Multi-Boundary Decomposition

**When**: The decomposed participant orchestrates multiple distinct sub-capabilities (e.g., a Platform with multiple sub-systems).

Each sub-capability is NOT nested inside the same box; instead generate multiple box boundaries in the Level N+1 diagram, one per sub-capability.

```mermaid
sequenceDiagram
    %% Stereotypes: Parent=actor | A_API=boundary, A_Logic=control | B_API=boundary, B_Logic=control
    participant Parent as "[Parent]"

    box "Sub-Capability A Boundary"
        participant A_API as "Capability A API"
        participant A_Logic as "Capability A Logic"
    end

    box "Sub-Capability B Boundary"
        participant B_API as "Capability B API"
        participant B_Logic as "Capability B Logic"
    end

    Parent->>A_API: [Request to A]
    A_API->>A_Logic: [Process A]
    A_Logic->>B_API: [Delegate to B]
    B_API->>B_Logic: [Process B]
    B_Logic-->>A_Logic: [B Result]
    A_Logic-->>A_API: [Combined Result]
    A_API-->>Parent: [Final Response]
```

Note: With multiple boundaries, each is a candidate for further decomposition independently.

---

## Level-by-Level Worked Examples

### Level 0 → Level 1: Decomposing `ECommercePlatform` (control)

**Parent (Level 0):**
```
Customer (actor) ->> ECommercePlatform (control): Place Order
```

**New Level 1 sub-process (01-EcommercePlatformBoundary/collaboration.md):**
```mermaid
sequenceDiagram
    %% Stereotypes: Customer=actor | Web=boundary, Cart=control, Order=control, CustomerDB=entity
    participant Customer as "Customer"

    box "E-commerce Platform Boundary"
        participant Web as "Web Frontend"
        participant Cart as "Shopping Cart Service"
        participant Order as "Order Management Service"
        participant CustomerDB as "Customer Database"
    end

    Customer->>Web: Browse & Place Order
    Web->>Cart: Update Cart
    Web->>Order: Submit Order
    Order->>CustomerDB: Retrieve Customer Info
    CustomerDB-->>Order: Customer Record
    Order-->>Web: Order Confirmation
    Web-->>Customer: Display Confirmation
```

---

### Level 1 → Level 2: Decomposing `OrderService` (control)

**Parent (Level 1):** `Order Management Service` inside E-commerce Platform

**New Level 2 sub-process (01-OrderManagementBoundary/collaboration.md):**
```mermaid
sequenceDiagram
    %% Stereotypes: Platform=actor | API=boundary, Validator=control, Engine=control, Repository=entity
    participant Platform as "E-commerce Platform"

    box "Order Management Service Boundary"
        participant API as "Order API"
        participant Validator as "Order Validator"
        participant Engine as "Order Processing Engine"
        participant Repository as "Order Repository"
    end

    Platform->>API: Create New Order
    API->>Validator: Validate Order Data
    Validator->>Engine: Process Valid Order
    Engine->>Repository: Store Order Record
    Repository-->>Engine: Order Stored
    Engine-->>API: Order Processed
    API-->>Platform: Order Confirmation
```

---

### Level 2 → Level 3: Decomposing `OrderEngine` (control)

**Parent (Level 2):** `Order Processing Engine` inside Order Management Service

**New Level 3 sub-process (01-OrderProcessingEngineBoundary/collaboration.md):**
```mermaid
sequenceDiagram
    %% Stereotypes: OrderService=actor | Workflow=boundary, Rules=control, Calculator=control, StateStore=entity
    participant OrderService as "Order Management Service"

    box "Order Processing Engine Boundary"
        participant Workflow as "Workflow Orchestrator"
        participant Rules as "Business Rules Engine"
        participant Calculator as "Price Calculator"
        participant StateStore as "Order State"
    end

    OrderService->>Workflow: Process Order Request
    Workflow->>Rules: Validate Business Rules
    Rules->>Calculator: Calculate Order Total
    Calculator->>StateStore: Update Order State
    StateStore-->>Workflow: State Updated
    Workflow-->>OrderService: Processing Complete
```

---

## Naming Conventions

### Sub-Folder Names
- Pattern: `[NN]-[ParticipantPascalCase]Boundary`
- NN = two-digit ordinal (01, 02, … 10, 11, …)
- PascalCase: remove spaces and special characters from participant label
- Examples:
  - `OrderService` → `01-OrderServiceBoundary`
  - `E-commerce Platform` → `01-EcommercePlatformBoundary`
  - `CPU` → `02-CPUBoundary`

### Participant Short Names (in Mermaid `participant` declarations)
- Use abbreviated PascalCase without spaces: `OrderAPI`, `TxProcessor`, `CustomerDB`
- Keep to ≤ 15 characters for readability
- Must be unique within a diagram

### Box Names
- Pattern: `[ParticipantLabel] Boundary`
- Use Title Case
- Omit `Boundary` suffix only when the name is naturally scoped (e.g., `E-commerce Platform`)
