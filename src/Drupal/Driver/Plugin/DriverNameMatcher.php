<?php

namespace Drupal\Driver\Plugin;

/**
 * Matching text input with entities using their machine names or labels.
 */
class DriverNameMatcher {

  /**
   * A set of items needing to be identified.
   *
   * The key is some human-friendly name, the value is preserved and is not
   * used for identification.
   *
   * @var array
   */
  protected $targets;

  /**
   * A set of items to match.
   *
   * The array values are the items machine names and the keys are the items
   * labels.
   *
   * @var array
   */
  protected $candidates;

  /**
   * A string that may precede the candidate's machine names.
   *
   * It should be ignored for identification purposes.
   *
   * @var string
   */
  protected $prefix;

  /**
   * A set of successfully matched items.
   *
   * @var array
   */
  protected $results;

  /**
   * Construct a driver name matcher object.
   *
   * @param mixed $candidates
   *   A set of candidate items where the keys are the items labels
   *   and the values are the items machine names.
   * @param string $prefix
   *   A string that may precede the candidate's machine names and should be
   *   ignored for identification purposes.
   */
  public function __construct($candidates, $prefix = NULL) {
    if (is_array($candidates)) {
      $this->candidates = $candidates;
    }
    else {
      throw new \Exception("Candidates for identification must be passed as an array with the labels as the keys and the machine names as the values.");
    }

    $this->prefix = $prefix;
    $this->results = [];
  }

  /**
   * Identifies a target from the pool of candidates.
   *
   * @param string $target
   *   A single string needing to be identified as an item in the candidates.
   *
   * @return string
   *   The machine name of the matching candidate, or NULL if none matched.
   */
  public function identify($target) {
    // Wrap the target in the appropriate array for identifySet().
    $targets = [$target => $target];
    $results = $this->identifySet($targets);
    // Get the first key of the results.
    reset($results);
    return key($results);
  }

  /**
   * Identifies the targets from the pool of candidates.
   *
   * @param mixed $targets
   *   A set of items needing to be identified. The key is some human-friendly
   *   name, the value is preserved and is not used for identification.
   *
   * @return array
   *   For each matched target, the key will be replaced with the machine name
   *   of the matching candidate, & the value will be preserved. Order may vary.
   */
  public function identifySet($targets) {
    if (is_array($targets)) {
      $this->targets = $targets;
    }
    else {
      throw new \Exception("Targets to be identified must be passed as an array with their human-friendly name as the keys and anything as the values.");
    }

    $mayHavePrefix = !is_null($this->prefix);
    $this->identifyByMethod("MachineNameExactly");
    $this->identifyByMethod("LabelExactly");
    if ($mayHavePrefix) {
      $this->identifyByMethod("MachineNameWithoutPrefix");
    }
    $this->identifyByMethod("MachineNameWithoutUnderscores");
    if ($mayHavePrefix) {
      $this->identifyByMethod("MachineNameWithoutPrefixAndUnderscores");
    }
    return $this->results;
  }

  /**
   * Gets the candidates that were not a match for any target.
   *
   * @return array
   *   An array of candidates.
   */
  public function getUnmatchedCandidates() {
    return $this->candidates;
  }

  /**
   * Gets the targets that were not a match for any candidate.
   *
   * @return array
   *   An array of targets.
   */
  public function getUnmatchedTargets() {
    return $this->targets;
  }

  /**
   * Iterates over candidates and targets looking for a match.
   *
   * @param string $method
   *   The last part of the name of a method of matching.
   */
  protected function identifyByMethod($method) {
    $methodFunctionName = "identifyBy" . $method;
    $matchedCandidates = [];
    foreach ($this->targets as $identifier => $value) {
      foreach ($this->candidates as $label => $machineName) {
        // Skip over candidates that describe fields already matched.
        if (in_array($machineName, $matchedCandidates)) {
          continue;
        }
        // If the identification method determines a match, remove the candidate
        // and target from future consideration, and save the result.
        if ($this->$methodFunctionName($identifier, $machineName, $label)) {
          $matchedCandidates[] = $machineName;
          unset($this->targets[$identifier]);
          $this->results[$machineName] = $value;
          break;
        }
      }
    }

    // Strip out the successfully matched candidates.
    $this->candidates = array_filter($this->candidates, function ($machineName) use ($matchedCandidates) {
      return !in_array($machineName, $matchedCandidates);
    });
  }

  /**
   * Matches an identifer against a machine name exactly.
   *
   * @param string $identifier
   *   The human-friendly name of the target.
   * @param string $machineName
   *   The machine name of the candidate.
   * @param string $label
   *   The label of the candidate.
   *
   * @return bool
   *   Whether a match was found using the identifier.
   */
  protected function identifyByMachineNameExactly($identifier, $machineName, $label) {
    return (mb_strtolower($identifier) === mb_strtolower($machineName));
  }

  /**
   * Matches an identifer against a label exactly.
   *
   * @param string $identifier
   *   The human-friendly name of the target.
   * @param string $machineName
   *   The machine name of the candidate.
   * @param string $label
   *   The label of the candidate.
   *
   * @return bool
   *   Whether a match was found using the identifier.
   */
  protected function identifyByLabelExactly($identifier, $machineName, $label) {
    return (mb_strtolower($identifier) === mb_strtolower($label));
  }

  /**
   * Matches an identifer against a machine name removing the prefix.
   *
   * @param string $identifier
   *   The human-friendly name of the target.
   * @param string $machineName
   *   The machine name of the candidate.
   * @param string $label
   *   The label of the candidate.
   *
   * @return bool
   *   Whether a match was found using the identifier.
   */
  protected function identifyByMachineNameWithoutPrefix($identifier, $machineName, $label) {
    if (substr($machineName, 0, 6) === $this->prefix) {
      $machineName = substr($machineName, 6);
    }
    return (mb_strtolower($identifier) === mb_strtolower($machineName));
  }

  /**
   * Matches an identifer against a machine name removing underscores from it.
   *
   * @param string $identifier
   *   The human-friendly name of the target.
   * @param string $machineName
   *   The machine name of the candidate.
   * @param string $label
   *   The label of the candidate.
   *
   * @return bool
   *   Whether a match was found using the identifier.
   */
  protected function identifyByMachineNameWithoutUnderscores($identifier, $machineName, $label) {
    $machineName = str_replace('_', ' ', $machineName);
    return (mb_strtolower($identifier) === mb_strtolower($machineName));
  }

  /**
   * Matches an identifer against a machine name, removing prefix & underscores.
   *
   * @param string $identifier
   *   The human-friendly name of the target.
   * @param string $machineName
   *   The machine name of the candidate.
   * @param string $label
   *   The label of the candidate.
   *
   * @return bool
   *   Whether a match was found using the identifier.
   */
  protected function identifyByMachineNameWithoutPrefixAndUnderscores($identifier, $machineName, $label) {
    if (substr($machineName, 0, 6) === "field_") {
      $machineName = substr($machineName, 6);
    }
    $machineName = str_replace('_', ' ', $machineName);
    return (mb_strtolower($identifier) === mb_strtolower($machineName));
  }

}
