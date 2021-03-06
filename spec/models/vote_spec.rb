# == Schema Information
#
# Table name: votes
#
#  id           :integer          not null, primary key
#  up           :boolean          default(TRUE), not null
#  person_id    :integer          not null
#  votable_type :string           not null
#  votable_id   :integer          not null
#

require 'rails_helper'

RSpec.describe Vote, type: :model do
  it { is_expected.to validate_exclusion_of(:up).in_array([nil]) }
  it { is_expected.to validate_presence_of(:person) }
  it { is_expected.to validate_presence_of(:votable) }

  it { is_expected.to belong_to(:person) }
  it { is_expected.to belong_to(:votable) }
end
