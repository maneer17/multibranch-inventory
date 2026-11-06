You've used the mechanism a few times in this project already without the full picture. Let's build it from the ground up.

**The problem it solves.** A class often needs another class to do its job. `TransferOrderService` needs `StockMovementService` to actually record the stock movements during `ship()` and `receive()`. The plain way to get one is to build it yourself:

```php
class TransferOrderService
{
    public function ship(...)
    {
        $service = new StockMovementService();
        $service->submit(...);
    }
}
```

This works today. The problem shows up the moment `StockMovementService` needs something of its own, say a logger:

```php
class StockMovementService
{
    public function __construct(private LoggerInterface $logger) {}
}
```

Now every `new StockMovementService()` anywhere in your codebase breaks, because none of them know how to also build a `LoggerInterface`. If you'd written `new StockMovementService()` in five different places, you're now fixing five places, each one needing to know how to construct a logger too.

**Dependency injection flips who's responsible for building the thing.** Instead of a class constructing its own dependencies, something needs them *handed to* it, usually through the constructor:

```php
class TransferOrderService
{
    public function __construct(private StockMovementService $stockMovementService) {}
}
```

This class no longer knows or cares how a `StockMovementService` gets built. It just declares "I need one of these," and trusts that whoever creates a `TransferOrderService` will supply it. That's the whole idea. Dependencies come from outside, injected in, rather than being constructed inside.

**Who actually builds it, then?** In plain PHP, you'd do it by hand:

```php
$stockMovementService = new StockMovementService();
$transferOrderService = new TransferOrderService($stockMovementService);
```

That's real, working dependency injection, no framework involved. The dependency, `$stockMovementService`, is built first and handed in. But now you've just moved the problem, whatever code creates a `TransferOrderService` has to also know how to build everything it needs, and everything those things need, all the way down. For a small app that's manageable. For a real one, with dozens of services each depending on several others, doing this by hand everywhere becomes its own maintenance burden.

**This is what Laravel's service container exists to automate.** You met `app(SkuGeneratorService::class)` early in this project. The container is a central registry that knows how to build any class, by looking at its constructor and recursively building whatever it asks for. When Laravel builds your `TransferOrderService` for you, usually because a controller asked for one, it:

1. Looks at `TransferOrderService`'s constructor, sees it needs a `StockMovementService`.
2. Looks at `StockMovementService`'s constructor, sees it needs nothing else right now (or, if it later needs a logger, the container builds that too).
3. Builds the `StockMovementService` first, then builds the `TransferOrderService` with it, then hands you the finished object.

You never write `new StockMovementService()` anywhere. You declare the need in a constructor parameter, and the container resolves it.

**Where this actually happens in your app.** A controller method like:

```php
public function store(TransferOrderService $service)
{
    $service->createRequest(...);
}
```

Laravel sees the type hint, resolves `TransferOrderService` through the container, which in turn resolves `StockMovementService` for it, all automatically, before your method body runs. You've been writing constructors ready for this the whole time without necessarily naming it.

**What `private` in the constructor parameter does, since you're using it already.** This:

```php
public function __construct(private StockMovementService $stockMovementService) {}
```

is shorthand PHP added a few versions back. Without it, you'd write:

```php
private StockMovementService $stockMovementService;

public function __construct(StockMovementService $stockMovementService)
{
    $this->stockMovementService = $stockMovementService;
}
```

Both do the same thing, declare the property and assign the constructor argument to it. The short form just removes the boilerplate. This is unrelated to dependency injection itself, it's a separate convenience, but the two show up together constantly because DI constructors are exactly where this shorthand earns its keep.

**Why this matters beyond convenience, the thing I flagged in your bug.** Testing. Say you want to test `TransferOrderService` without actually running real stock movements, real database transactions, real locking, just to verify `ship()` calls `submit()` with the right arguments. With dependency injection, you can hand the service a fake `StockMovementService` instead of a real one:

```php
$fakeStockService = Mockery::mock(StockMovementService::class);
$fakeStockService->shouldReceive('submit')->once();

$transferOrderService = new TransferOrderService($fakeStockService);
```

Without DI, if `ship()` did `new StockMovementService()` internally, there'd be no way to substitute anything, the real service, with its real database calls, would always run. You haven't needed this yet, since your tests use real factories and a real test database throughout, which is a legitimate choice for the level you're at. But it's the actual payoff DI buys you once a project gets large enough that hitting the real database for every single test becomes slow.

**The bug you had, restated with this in mind.** You correctly declared the dependency in the constructor, Laravel would have handed you a real, properly-built `StockMovementService`. But the line inside the body threw that away and built a second one by hand, `new StockMovementService()`. It happened to produce the same result today, because `StockMovementService` currently needs nothing itself. But it silently defeated the whole mechanism, if `StockMovementService` ever gained a dependency, your manual `new` would break while the container-resolved version would have kept working untouched.

Does the shift make sense now, a class declares what it needs, something outside builds and hands it in, and Laravel's container automates the "building and handing in" part so you don't do it by hand everywhere?
