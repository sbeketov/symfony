<?php

namespace App\Message;

class CommentMessage
{
    /**
     * @param int $id
     * @param array $context
     */
    public function __construct(
        private int $id,
        private array $context = [],
    ){}

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

}