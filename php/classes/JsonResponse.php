<?php

namespace own;

class JsonResponse {
  public function __construct(
    public int $code = 200,
    public mixed $data = null,
    public ?string $message = null,
  ) {
  }
  public static function success(mixed $data = null): self {
    return new self(200, $data);
  }
  public static function error(string $message, int $code = 400): self {
    return new self($code, null, $message);
  }
  public function toArray(): array {
    return [
      'code' => $this->code,
      'data' => $this->data,
      'message' => $this->message
    ];
  }
}
