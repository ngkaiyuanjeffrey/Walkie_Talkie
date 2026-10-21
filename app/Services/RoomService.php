<?php
declare(strict_types=1);

final class RoomService
{
    public function __construct(private readonly string $roomDir, private readonly int $maxPeers, private readonly int $floorTimeoutMs)
    {
        if (!is_dir($roomDir)) {
            mkdir($roomDir, 0775, true);
        }
    }

    public function join(string $channel, string $peer, string $nickname): array
    {
        return $this->mutate($channel, function (array &$room) use ($peer, $nickname): array {
            $this->prune($room);
            if (!isset($room['peers'][$peer]) && count($room['peers']) >= $this->maxPeers) {
                throw new RuntimeException('This channel is full.');
            }
            $room['peers'][$peer] = ['id' => $peer, 'nickname' => $nickname, 'last_seen' => microtime(true)];
            $room['sequence']++;
            $room['events'][] = ['id' => $room['sequence'], 'type' => 'peer_joined', 'peer' => $peer, 'nickname' => $nickname];
            return $this->snapshot($room);
        });
    }

    public function poll(string $channel, string $peer, int $after): array
    {
        return $this->mutate($channel, function (array &$room) use ($peer, $after): array {
            $this->prune($room);
            if (isset($room['peers'][$peer])) {
                $room['peers'][$peer]['last_seen'] = microtime(true);
            }
            return ['events' => array_values(array_filter($room['events'], fn (array $event): bool => $event['id'] > $after)), 'state' => $this->snapshot($room)];
        });
    }

    public function ptt(string $channel, string $peer, string $nickname, bool $release): array
    {
        return $this->mutate($channel, function (array &$room) use ($peer, $nickname, $release): array {
            $this->prune($room);
            if ($release) {
                if (($room['speaker']['peer'] ?? null) === $peer) {
                    $room['speaker'] = null;
                    $room['sequence']++;
                    $room['events'][] = ['id' => $room['sequence'], 'type' => 'speaker_stopped', 'peer' => $peer];
                }
                return ['granted' => true, 'state' => $this->snapshot($room)];
            }
            if ($room['speaker'] !== null && ($room['speaker']['peer'] ?? null) !== $peer) {
                return ['granted' => false, 'busy_by' => $room['speaker']['nickname'], 'state' => $this->snapshot($room)];
            }
            $room['speaker'] = ['peer' => $peer, 'nickname' => $nickname, 'until' => microtime(true) + ($this->floorTimeoutMs / 1000)];
            $room['sequence']++;
            $room['events'][] = ['id' => $room['sequence'], 'type' => 'speaker_started', 'peer' => $peer, 'nickname' => $nickname];
            return ['granted' => true, 'state' => $this->snapshot($room)];
        });
    }

    public function signal(string $channel, string $from, array $message): array
    {
        return $this->mutate($channel, function (array &$room) use ($from, $message): array {
            $this->prune($room);
            $target = (string) ($message['to'] ?? '');
            if ($target === '' || !isset($room['peers'][$target])) {
                return ['delivered' => false];
            }
            $room['sequence']++;
            $room['events'][] = ['id' => $room['sequence'], 'type' => 'signal', 'from' => $from, 'to' => $target, 'data' => $message['data'] ?? null];
            return ['delivered' => true];
        });
    }

    public function leave(string $channel, string $peer): array
    {
        return $this->mutate($channel, function (array &$room) use ($peer): array {
            unset($room['peers'][$peer]);
            if (($room['speaker']['peer'] ?? null) === $peer) {
                $room['speaker'] = null;
            }
            $room['sequence']++;
            $room['events'][] = ['id' => $room['sequence'], 'type' => 'peer_left', 'peer' => $peer];
            return ['left' => true];
        });
    }

    private function mutate(string $channel, callable $callback): array
    {
        $path = $this->roomDir . '/' . hash('sha256', $channel) . '.json';
        $handle = fopen($path, 'c+');
        if (!$handle || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('Room storage is unavailable.');
        }
        $contents = stream_get_contents($handle) ?: '';
        $room = json_decode($contents, true) ?: ['peers' => [], 'speaker' => null, 'events' => [], 'sequence' => 0];
        try {
            $result = $callback($room);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($room));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        return $result;
    }

    private function prune(array &$room): void
    {
        $now = microtime(true);
        foreach ($room['peers'] as $id => $peer) {
            if ($peer['last_seen'] < $now - 20) {
                unset($room['peers'][$id]);
                $room['sequence']++;
                $room['events'][] = ['id' => $room['sequence'], 'type' => 'peer_left', 'peer' => $id];
            }
        }
        if ($room['speaker'] !== null && $room['speaker']['until'] < $now) {
            $room['sequence']++;
            $room['events'][] = ['id' => $room['sequence'], 'type' => 'speaker_stopped', 'peer' => $room['speaker']['peer']];
            $room['speaker'] = null;
        }
        $room['events'] = array_slice($room['events'], -200);
    }

    private function snapshot(array $room): array
    {
        return ['peers' => array_values($room['peers']), 'speaker' => $room['speaker'], 'sequence' => $room['sequence']];
    }
}
